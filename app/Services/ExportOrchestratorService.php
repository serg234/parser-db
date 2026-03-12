<?php

namespace App\Services;

use App\Models\Dump;
use App\Models\ExportFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * High-level export orchestration.
 *
 * Coordinates the full pipeline:
 * - create temporary database
 * - import SQL dump
 * - detect content table
 * - extract items and sanitize content
 * - generate export file and save metadata in DB
 *
 * Used by `ExportController` actions.
 */
class ExportOrchestratorService
{
    /**
     * Normalize error message to valid UTF-8 string for DB storage.
     */
    private function normalizeErrorMessage(string $message): string
    {
        if ($message === '') {
            return '';
        }

        if (preg_match('//u', $message) === 1) {
            return $message;
        }

        $converted = @iconv('CP866', 'UTF-8//IGNORE', $message);
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }

        $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $message);
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }

        // Last resort: drop invalid bytes.
        return (string) @iconv('UTF-8', 'UTF-8//IGNORE', $message);
    }

    /**
     * Service composition via constructor injection.
     */
    public function __construct(
        private DumpStorageService $dumpStorage,
        private SqlImportService $sqlImport,
        private ContentSchemaDetectorService $schemaDetector,
        private NewsExtractorService $extractor,
        private ContentSanitizerService $sanitizer,
        private NewsExportService $exporter,
    ) {
    }

    /**
     * Generate export for a single source.
     *
     * @throws \Throwable any import/detect/extract/export exception will bubble up
     */
    public function generateForDump(Dump $dump, string $format): ExportFile
    {
        $this->dumpStorage->ensureDirectories();

        $disk = Storage::disk('local');
        $absSql = $disk->path($dump->relative_path);

        $tmpDb = $this->sqlImport->createTemporaryDatabase();

        try {
            $this->sqlImport->importSqlFile($tmpDb, $absSql);

            $postsTable = $this->schemaDetector->detectPostsTable($tmpDb);
            $items = $this->extractor->extract($tmpDb, $postsTable);

            $items = array_map(function (array $item) use ($format) {
                $title = (string) ($item['title'] ?? '');
                $contentRaw = (string) ($item['content'] ?? '');

                $formatLower = strtolower($format);

                if ($formatLower === 'xml') {
                    // XML: очищенный HTML с базовым форматированием.
                    $content = $this->sanitizer->sanitize($contentRaw);
                } else {
                    // CSV / TXT и прочее: очищенный плейн-текст.
                    $content = $this->sanitizer->toPlainText($contentRaw);
                }

                return [
                    'title' => $title,
                    'content' => $content,
                ];
            }, $items);

            $filenameBase = Str::slug(pathinfo($dump->original_name, PATHINFO_FILENAME) ?: 'export', '_');
            $filename = $filenameBase . '__' . now()->format('Y-m-d_His') . '.' . strtolower($format);

            $relativePath = trim(config('parser.storage.exports_dir', 'exports'), '/')
                . '/single/'
                . $dump->id
                . '/'
                . $filename;

            $meta = $this->exporter->export($items, $format, $relativePath);

            /** @var ExportFile $export */
            $export = ExportFile::query()->create([
                'dump_id' => $dump->id,
                'type' => ExportFile::TYPE_SINGLE,
                'format' => strtolower($format),
                'filename' => $filename,
                'relative_path' => $relativePath,
                'size_bytes' => $meta['size_bytes'],
                'items_count' => $meta['items_count'],
            ]);

            $dump->forceFill([
                'last_parsed_at' => now(),
                'last_error' => null,
            ])->save();

            return $export;
        } catch (\Throwable $e) {
            $dump->forceFill([
                'last_error' => $this->normalizeErrorMessage($e->getMessage()),
            ])->save();

            throw $e;
        } finally {
            $this->sqlImport->dropDatabase($tmpDb);
        }
    }

    /**
     * Generate a single merged export file from multiple sources.
     *
     * @param array<int, Dump> $dumps
     * @throws \Throwable
     */
    public function generateMerged(array $dumps, string $format): ExportFile
    {
        if (count($dumps) < 2) {
            throw new \InvalidArgumentException('Select at least two sources for merging.');
        }

        $this->dumpStorage->ensureDirectories();

        $itemsAll = [];
        foreach ($dumps as $dump) {
            $disk = Storage::disk('local');
            $absSql = $disk->path($dump->relative_path);

            $tmpDb = $this->sqlImport->createTemporaryDatabase();

            try {
                $this->sqlImport->importSqlFile($tmpDb, $absSql);
                $postsTable = $this->schemaDetector->detectPostsTable($tmpDb);
                $items = $this->extractor->extract($tmpDb, $postsTable);

                foreach ($items as $item) {
                    $title = (string) ($item['title'] ?? '');
                    $contentRaw = (string) ($item['content'] ?? '');

                    $formatLower = strtolower($format);

                    if ($formatLower === 'xml') {
                        $content = $this->sanitizer->sanitize($contentRaw);
                    } else {
                        $content = $this->sanitizer->toPlainText($contentRaw);
                    }

                    $itemsAll[] = [
                        'title' => $title,
                        'content' => $content,
                    ];
                }
            } finally {
                $this->sqlImport->dropDatabase($tmpDb);
            }
        }

        $filename = 'merged__' . now()->format('Y-m-d_His') . '__' . Str::random(6) . '.' . strtolower($format);
        $relativePath = trim(config('parser.storage.exports_dir', 'exports'), '/')
            . '/merged/'
            . $filename;

        $meta = $this->exporter->export($itemsAll, $format, $relativePath);

        return DB::transaction(function () use ($dumps, $format, $filename, $relativePath, $meta) {
            /** @var ExportFile $export */
            $export = ExportFile::query()->create([
                'dump_id' => null,
                'type' => ExportFile::TYPE_MERGED,
                'format' => strtolower($format),
                'filename' => $filename,
                'relative_path' => $relativePath,
                'size_bytes' => $meta['size_bytes'],
                'items_count' => $meta['items_count'],
            ]);

            $pairs = [];
            foreach (array_values($dumps) as $idx => $dump) {
                $pairs[$dump->id] = ['position' => $idx + 1];
            }

            $export->dumps()->sync($pairs);

            return $export;
        });
    }
}

