<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'headline' => 'Learning & Development Specialist',
            'contact_email' => fake()->unique()->safeEmail(),
            'phone' => '0851-5648-0171',
            'location' => 'Central Jakarta',
            'portfolio_url' => 'https://example.com/portfolio',
            'photo_url' => 'https://res.cloudinary.com/demo/image/upload/sample.jpg',
            'cv_url' => 'https://res.cloudinary.com/demo/raw/upload/cv.pdf',
            'cv_filename' => 'cv.pdf',
            'cv_text' => str_repeat('Managed corporate training projects for PT KAI and Mitsubishi Motors. ', 10),
            'cv_parse_status' => 'parsed',
            'cv_parsed_at' => now(),
        ];
    }

    /**
     * A profile whose CV yielded no usable text, e.g. a scanned PDF.
     */
    public function withoutCvText(): static
    {
        return $this->state(fn (): array => [
            'cv_text' => null,
            'cv_parse_status' => 'empty',
        ]);
    }
}
