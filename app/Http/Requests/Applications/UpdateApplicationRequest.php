<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:150'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'job_post' => ['nullable', 'string', 'max:20000'],
            'mode' => ['required', Rule::in(['manual', 'ai'])],
            'field_values' => ['array'],
            'field_values.*' => ['nullable'],
        ];
    }
}
