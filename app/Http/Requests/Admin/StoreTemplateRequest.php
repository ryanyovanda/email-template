<?php

namespace App\Http\Requests\Admin;

use App\Models\EmailTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $template = $this->route('template');
        $templateId = $template instanceof EmailTemplate ? $template->id : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable', 'string', 'max:140', 'alpha_dash',
                Rule::unique('email_templates', 'slug')->ignore($templateId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'thumbnail_url' => ['nullable', 'url', 'max:500'],
            'html' => ['required', 'string', 'max:200000'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],

            'fields' => ['array'],
            'fields.*.token' => ['required', 'string', 'max:60'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.type' => ['required', Rule::in(['text', 'textarea', 'email', 'url', 'image', 'list'])],
            'fields.*.help' => ['nullable', 'string', 'max:200'],
            'fields.*.default' => ['nullable', 'string', 'max:500'],
            'fields.*.ai' => ['boolean'],
            'fields.*.required' => ['boolean'],
        ];
    }
}
