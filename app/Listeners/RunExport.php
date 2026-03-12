<?php

namespace App\Listeners;

use App\Events\ExportRequested;
use App\Models\Dump;
use App\Models\ExportFile;
use App\Models\ExportTask;
use App\Services\ExportOrchestratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Queue listener that runs export pipeline in background.
 */
class RunExport implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private ExportOrchestratorService $orchestrator,
    ) {
    }

    public function handle(ExportRequested $event): void
    {
        $format = $event->format;
        $type = $event->type;
        $taskId = $event->taskId;

        //sleep(5);

        $task = $taskId ? ExportTask::find($taskId) : null;

        if ($task) {
            $task->update(['status' => ExportTask::STATUS_RUNNING, 'error' => null]);
        }

        try {
            if ($type === ExportFile::TYPE_MERGED) {
                $dumps = Dump::query()
                    ->whereIn('id', $event->dumpIds)
                    ->orderBy('id')
                    ->get()
                    ->all();

                if (count($dumps) >= 2) {
                    $this->orchestrator->generateMerged($dumps, $format);
                }
            } else {
                // Default: single exports for each selected dump.
                $dumps = Dump::query()
                    ->whereIn('id', $event->dumpIds)
                    ->orderBy('id')
                    ->get();

                foreach ($dumps as $dump) {
                    $this->orchestrator->generateForDump($dump, $format);
                }
            }

            if ($task) {
                $task->update(['status' => ExportTask::STATUS_DONE]);
            }
        } catch (\Throwable $e) {
            if ($task) {
                $task->update([
                    'status' => ExportTask::STATUS_FAILED,
                    'error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }
}

