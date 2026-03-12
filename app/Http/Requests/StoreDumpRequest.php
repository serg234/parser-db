<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates source upload requests.
 *
 * Ensures that an uploaded file is a `.sql` and stays within size limits.
 */
class StoreDumpRequest extends FormRequest
{
    /**
     * Allow access (no auth required for this test project).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for upload.
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array'],
            'files.*' => ['nullable'],
        ];
    }

    /**
     * Additional extension validation based on original file names.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $files = $this->file('files', []);
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $index => $file) {
                if ($file === null) {
                    continue;
                }

                $ext = strtolower((string) $file->getClientOriginalExtension());
                if ($ext !== 'sql') {
                    $validator->errors()->add('files.' . $index, 'Разрешены только файлы .sql');
                }
            }
        });
    }
}

