<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates "merge export" requests.
 *
 * Used by `ExportController@merge` to create a single file from multiple sources.
 */
class MergeExportRequest extends FormRequest
{
    /**
     * Allow access (no auth required for this test project).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Require:
     * - at least two selected source ids
     * - a supported export format
     */
    public function rules(): array
    {
        return [
            'dump_ids' => ['required', 'array', 'min:2'],
            'dump_ids.*' => ['integer', 'exists:dumps,id'],
            'format' => ['required', 'in:xml,csv,txt'],
        ];
    }
}

