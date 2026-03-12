<?php

namespace App\Http\Controllers;

use App\Events\ExportRequested;
use App\Models\ExportFile;
use App\Models\ExportTask;
use App\Http\Requests\GenerateExportRequest;
use App\Http\Requests\MergeExportRequest;
use App\Models\Dump;
use App\Services\DumpStorageService;
use App\Services\ExportOrchestratorService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * UI controller for generating and downloading export files.
 *
 * Uses:
 * - `DumpStorageService` to list available sources
 * - `ExportOrchestratorService` to run full export pipeline
 */
class ExportController extends Controller
{
    /**
     * @param DumpStorageService $dumpStorage Sources listing/sync
     * @param ExportOrchestratorService $orchestrator Full export pipeline
     */
    public function __construct(
        private DumpStorageService $dumpStorage,
        private ExportOrchestratorService $orchestrator,
    ) {
    }

    /**
     * Show export page: sources selection and generated files list.
     */
    public function index(): View
    {
        $dumps = $this->dumpStorage->list();
        $exports = ExportFile::query()
            ->with(['dump', 'dumps'])
            ->latestFirst()
            ->get();

        return view('exports.index', [
            'dumps' => $dumps,
            'exports' => $exports,
        ]);
    }

    /**
     * Generate export files for selected sources (one file per source).
     */
    public function generate(GenerateExportRequest $request)
    {
        $validated = $request->validated();

        $formatValue = $validated['format'] ?? null;
        $format = is_array($formatValue) ? (string) ($formatValue[0] ?? '') : (string) $formatValue;

        $dumpIdsValue = $validated['dump_ids'] ?? [];
        $dumpIds = is_array($dumpIdsValue) ? $dumpIdsValue : [];

        $dumps = Dump::query()->whereIn('id', $dumpIds)->orderBy('id')->get();
        if ($dumps->isEmpty()) {
            return $this->respondExportError($request, 'Не выбраны источники.');
        }

        $task = ExportTask::query()->create([
            'type' => ExportFile::TYPE_SINGLE,
            'format' => strtolower($format),
            'status' => ExportTask::STATUS_PENDING,
        ]);

        event(new ExportRequested($dumps->pluck('id')->all(), $format, ExportFile::TYPE_SINGLE, $task->id));

        return $this->respondExportAccepted($request, $task);
    }

    /**
     * Generate a single merged export file from selected sources.
     */
    public function merge(MergeExportRequest $request)
    {
        $validated = $request->validated();

        $formatValue = $validated['format'] ?? null;
        $format = is_array($formatValue) ? (string) ($formatValue[0] ?? '') : (string) $formatValue;

        $dumpIdsValue = $validated['dump_ids'] ?? [];
        $dumpIds = is_array($dumpIdsValue) ? $dumpIdsValue : [];

        if (count($dumpIds) < 2) {
            return $this->respondExportError($request, 'Для объединения выбери как минимум два источника.');
        }

        $task = ExportTask::query()->create([
            'type' => ExportFile::TYPE_MERGED,
            'format' => strtolower($format),
            'status' => ExportTask::STATUS_PENDING,
        ]);

        event(new ExportRequested($dumpIds, $format, ExportFile::TYPE_MERGED, $task->id));

        return $this->respondExportAccepted($request, $task);
    }

    /**
     * Download a generated export file.
     */
    public function download(ExportFile $exportFile): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        if (!$disk->exists($exportFile->relative_path)) {
            abort(404);
        }

        return response()->download(
            $disk->path($exportFile->relative_path),
            $exportFile->filename
        );
    }

    /**
     * Return current status of background export task.
     */
    public function taskStatus(ExportTask $task)
    {
        return response()->json([
            'id' => $task->id,
            'type' => $task->type,
            'format' => $task->format,
            'status' => $task->status,
            'error' => $task->error,
        ]);
    }

    private function respondExportAccepted($request, ExportTask $task)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'task_id' => $task->id,
                'status' => $task->status,
            ]);
        }

        return redirect()
            ->route('exports.index')
            ->with('status', 'Задача экспорта поставлена в очередь.');
    }

    private function respondExportError($request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $message,
            ], 422);
        }

        return redirect()
            ->route('exports.index')
            ->withErrors(['dump_ids' => $message]);
    }
}

