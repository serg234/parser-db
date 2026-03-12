<?php

namespace App\Events;

class ExportRequested
{
    /**
     * @param array<int,int> $dumpIds
     * @param string $format
     * @param string $type
     * @param int|null $taskId
     */
    public function __construct(
        public array $dumpIds,
        public string $format,
        public string $type,
        public ?int $taskId = null
    ) {
    }
}

