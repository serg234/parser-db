<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dump model.
 *
 * Represents an uploaded SQL file (source) stored on disk and indexed in DB.
 * Related export files are stored via `exportFiles()` relation.
 */
class Dump extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_name',
        'stored_name',
        'relative_path',
        'size_bytes',
        'checksum',
        'last_parsed_at',
        'last_error',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'last_parsed_at' => 'datetime',
    ];

    /**
     * Export files generated from this source.
     */
    public function exportFiles(): HasMany
    {
        return $this->hasMany(ExportFile::class);
    }

    /**
     * Order by newest records first.
     */
    public function scopeOrderLatest($query)
    {
        return $query->orderByDesc('id');
    }
}

