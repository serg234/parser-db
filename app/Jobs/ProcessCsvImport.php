<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Models\User;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $filePath,
        private int    $importId
    ) {}

    public function handle()
    {
        Redis::set("import:{$this->importId}:status", 'processing');
        $lines = file($this->filePath);
        $total = count($lines) - 1;
        $processed = 0;

        if ($handle = fopen($this->filePath, 'r')) {
            fgetcsv($handle); // header
            while ($row = fgetcsv($handle)) {
                [$name, $email] = $row;
                User::updateOrCreate(
                    ['email'=>$email],
                    ['name'=>$name]
                );
                $processed++;
                // обновляем прогресс
                Redis::set(
                    "import:{$this->importId}:progress",
                    intval($processed/$total*100)
                );
            }
            fclose($handle);
        }

        Redis::set("import:{$this->importId}:status", 'completed');
    }
}
