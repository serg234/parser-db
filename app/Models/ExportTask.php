<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ExportTask model.
 *
 * Tracks background export jobs status for UI polling.
 */
class ExportTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'type',
        'format',
        'status',
        'error',
    ];
}

