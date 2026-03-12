<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDumpRequest;
use App\Models\Dump;
use App\Services\DumpStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * UI controller for managing SQL dump files (sources).
 *
 * Delegates all file operations to `DumpStorageService`.
 */
class DumpController extends Controller
{
    /**
     * @param DumpStorageService $dumpStorage Service for listing/storing/deleting sources
     */
    public function __construct(private DumpStorageService $dumpStorage)
    {
    }

    /**
     * Show sources list and upload form.
     */
    public function index(): View
    {
        $dumps = $this->dumpStorage->list();

        return view('dumps.index', [
            'dumps' => $dumps,
        ]);
    }

    /**
     * Upload new `.sql` files.
     */
    public function store(StoreDumpRequest $request)
    {
        $files = $request->file('files', []);
        if (!is_array($files)) {
            $files = [$files];
        }

        $createdNames = [];
        foreach ($files as $file) {
            if ($file === null || !$file->isValid()) {
                continue;
            }

            $dump = $this->dumpStorage->storeUploaded($file);
            $createdNames[] = $dump->original_name;
        }

        $message = match (count($createdNames)) {
            0 => 'Файлы не были загружены.',
            1 => 'Файл добавлен: ' . $createdNames[0],
            default => 'Файлы добавлены: ' . implode(', ', $createdNames),
        };

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => $message,
                'files_count' => count($createdNames),
            ]);
        }

        return redirect()
            ->route('dumps.index')
            ->with('status', $message);
    }

    /**
     * Delete source file and related generated exports.
     */
    public function destroy(Dump $dump): RedirectResponse
    {
        $this->dumpStorage->delete($dump);

        return redirect()
            ->route('dumps.index')
            ->with('status', 'Файл удален.');
    }
}

