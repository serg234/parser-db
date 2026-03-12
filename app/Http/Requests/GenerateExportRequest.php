<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates "generate export" requests.
 *
 * Used by `ExportController@generate` to create one export file per selected source.
 */
class GenerateExportRequest extends FormRequest
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
     * - at least one selected source id
     * - a supported export format
     */
    public function rules(): array
    {
        return [
            'dump_ids' => ['required', 'array', 'min:1'],
            'dump_ids.*' => ['integer', 'exists:dumps,id'],
            'format' => ['required', 'in:xml,csv,txt'],
        ];
    }
}

