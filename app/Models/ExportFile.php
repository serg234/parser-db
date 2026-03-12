<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ExportFile model.
 *
 * Represents a generated export file stored on disk and indexed in DB.
 * - single: belongs to one Dump via `dump()`
 * - merged: linked to multiple Dump records via `dumps()` pivot
 */
class ExportFile extends Model
{
    use HasFactory;

    public const TYPE_SINGLE = 'single';
    public const TYPE_MERGED = 'merged';

    protected $fillable = [
        'dump_id',
        'type',
        'format',
        'filename',
        'relative_path',
        'size_bytes',
        'items_count',
    ];

    protected $casts = [
        'dump_id' => 'integer',
        'size_bytes' => 'integer',
        'items_count' => 'integer',
    ];

    /**
     * Single-export source.
     */
    public function dump(): BelongsTo
    {
        return $this->belongsTo(Dump::class);
    }

    /**
     * Sources for merged export.
     */
    public function dumps(): BelongsToMany
    {
        return $this->belongsToMany(Dump::class, 'export_file_dump')
            ->withPivot(['position'])
            ->withTimestamps()
            ->orderBy('export_file_dump.position');
    }

    /**
     * Order by newest records first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('id');
    }
}

