<?php

namespace App\Services;

use App\Models\Dump;
use App\Models\ExportFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles storage operations for SQL dump files.
 *
 * Responsibilities:
 * - keep local storage folders in place
 * - store uploaded `.sql` files
 * - sync disk files with `dumps` DB table
 * - delete source file together with related generated exports
 *
 * This service is used by controllers and orchestration services.
 */
class DumpStorageService
{
    /**
     * Relative directory name (inside `storage/app`) for uploaded dump files.
     */
    public function dumpsDir(): string
    {
        return trim((string) config('parser.storage.dumps_dir', 'dumps'), '/');
    }

    /**
     * Ensure required storage directories exist.
     */
    public function ensureDirectories(): void
    {
        Storage::disk('local')->makeDirectory($this->dumpsDir());
        Storage::disk('local')->makeDirectory($this->exportsDir());
    }

    /**
     * Relative directory name (inside `storage/app`) for generated exports.
     */
    public function exportsDir(): string
    {
        return trim((string) config('parser.storage.exports_dir', 'exports'), '/');
    }

    /**
     * Sync `.sql` files on disk with rows in DB.
     *
     * Creates missing `Dump` records for new files and deletes DB records
     * for files that no longer exist on disk.
     *
     * @return array{created:int, deleted:int, files_count:int}
     */
    public function sync(): array
    {
        $this->ensureDirectories();

        $disk = Storage::disk('local');
        $dir = $this->dumpsDir();

        $files = collect($disk->files($dir))
            ->filter(fn (string $p) => Str::endsWith(Str::lower($p), '.sql'))
            ->values();

        $existing = Dump::query()->get()->keyBy('relative_path');

        $created = 0;
        foreach ($files as $relativePath) {
            if ($existing->has($relativePath)) {
                continue;
            }

            $abs = $disk->path($relativePath);
            $storedName = basename($relativePath);

            Dump::query()->create([
                'original_name' => $storedName,
                'stored_name' => $storedName,
                'relative_path' => $relativePath,
                'size_bytes' => (int) @filesize($abs),
                'checksum' => $this->checksum($abs),
            ]);

            $created++;
        }

        $deleted = 0;
        foreach ($existing as $dump) {
            if (!$files->contains($dump->relative_path)) {
                $dump->delete();
                $deleted++;
            }
        }

        return [
            'created' => $created,
            'deleted' => $deleted,
            'files_count' => $files->count(),
        ];
    }

    /**
     * List sources (ensures synchronization first).
     */
    public function list(): Collection
    {
        $this->sync();

        return Dump::query()
            ->orderLatest()
            ->get();
    }

    /**
     * Store uploaded `.sql` file and create DB record.
     */
    public function storeUploaded(UploadedFile $file): Dump
    {
        $this->ensureDirectories();

        $disk = Storage::disk('local');
        $dir = $this->dumpsDir();

        $originalName = $file->getClientOriginalName();
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $storedName = Str::slug($base, '_') . '__' . now()->format('Ymd_His') . '__' . Str::random(6) . '.sql';
        $relativePath = $file->storeAs($dir, $storedName, 'local');

        $abs = $disk->path($relativePath);

        return Dump::query()->create([
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'relative_path' => $relativePath,
            'size_bytes' => (int) @filesize($abs),
            'checksum' => $this->checksum($abs),
        ]);
    }

    /**
     * Delete source file and related export files.
     */
    public function delete(Dump $dump): void
    {
        DB::transaction(function () use ($dump) {
            $disk = Storage::disk('local');

            if ($disk->exists($dump->relative_path)) {
                $disk->delete($dump->relative_path);
            }

            $exportDirs = [];

            // Delete single exports linked directly to this dump
            $dump->exportFiles()->each(function ($export) use ($disk, &$exportDirs) {
                if ($disk->exists($export->relative_path)) {
                    $disk->delete($export->relative_path);
                }

                $dir = trim(dirname($export->relative_path), '/');
                if ($dir !== '' && !in_array($dir, $exportDirs, true)) {
                    $exportDirs[] = $dir;
                }
            });

            // Delete merged exports that reference this dump via pivot
            $mergedExports = ExportFile::query()
                ->where('type', ExportFile::TYPE_MERGED)
                ->whereHas('dumps', function ($query) use ($dump) {
                    $query->where('dumps.id', $dump->id);
                })
                ->get();

            foreach ($mergedExports as $export) {
                if ($disk->exists($export->relative_path)) {
                    $disk->delete($export->relative_path);
                }

                $dir = trim(dirname($export->relative_path), '/');
                if ($dir !== '' && !in_array($dir, $exportDirs, true)) {
                    $exportDirs[] = $dir;
                }

                // Удаляем сам merged‑экспорт; pivot‑связи чистятся каскадом.
                $export->delete();
            }

            foreach ($exportDirs as $dir) {
                // Удаляем пустые папки с экспортами (single или merged), если в них ничего не осталось.
                if (empty($disk->files($dir)) && empty($disk->directories($dir))) {
                    $disk->deleteDirectory($dir);
                }
            }

            $dump->delete();
        });
    }

    /**
     * Calculate checksum for source file. Used to detect changes.
     */
    private function checksum(string $absolutePath): ?string
    {
        if (!is_file($absolutePath)) {
            return null;
        }

        $hash = @hash_file('sha256', $absolutePath);

        return $hash !== false ? $hash : null;
    }
}

