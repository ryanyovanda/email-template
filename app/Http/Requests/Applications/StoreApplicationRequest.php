<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email_template_id' => [
                'required',
                Rule::exists('email_templates', 'id')->where('is_active', true),
            ],
            // Visibility is enforced in the controller, where the current user
            // is available to scope the lookup.
            'title' => ['nullable', 'string', 'max:150'],
        ];
    }
}
