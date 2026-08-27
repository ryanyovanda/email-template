<?php

namespace App\Http\Requests\Applicant;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTemplateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brief' => ['required', 'string', 'min:20', 'max:1200'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'brief.required' => 'Describe the template you want.',
            'brief.min' => 'Give the AI a bit more to work with — say what the role is and how it should feel.',
            'accept_terms.accepted' => 'You need to accept the terms before we can design a template for you.',
        ];
    }
}
