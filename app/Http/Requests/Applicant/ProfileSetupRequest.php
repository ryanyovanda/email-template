<?php

namespace App\Http\Requests\Applicant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileSetupRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:160'],
            'contact_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:120'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'cv_text' => ['nullable', 'string', 'max:40000'],

            'photo' => [
                'nullable',
                'image',
                Rule::file()
                    ->types(config('emailcv.uploads.photo.mimes'))
                    ->max(config('emailcv.uploads.photo.max_kb')),
            ],
            'cv' => [
                'nullable',
                Rule::file()
                    ->types(config('emailcv.uploads.cv.mimes'))
                    ->max(config('emailcv.uploads.cv.max_kb')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.max' => 'The photo may not be larger than 4 MB.',
            'cv.max' => 'The CV may not be larger than 8 MB.',
        ];
    }
}
