<?php

namespace App\Http\Requests\Applicant;

use App\Services\Templates\TemplateValidator;
use Illuminate\Foundation\Http\FormRequest;

class BuildTemplateRequest extends FormRequest
{
    /**
     * The HTML is deliberately allowed past TemplateValidator::MAX_HTML_BYTES
     * here so the validator can reject it with an explanation the author can
     * act on, rather than a bare "field is too large".
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'description' => ['nullable', 'string', 'max:400'],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'html' => ['required', 'string', 'max:'.(TemplateValidator::MAX_HTML_BYTES * 4)],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give your template a name so you can find it later.',
            'accent_color.regex' => 'Pick an accent colour as a six-digit hex value, like #E86A33.',
            'html.required' => 'There is no HTML here yet. Load one of the examples to start from.',
            'accept_terms.accepted' => 'You need to accept the terms before your template can be saved.',
        ];
    }
}
