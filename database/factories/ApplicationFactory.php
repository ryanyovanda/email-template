<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email_template_id' => EmailTemplate::factory(),
            'title' => 'Application — '.fake()->company(),
            'company' => fake()->company(),
            'position' => 'Senior Associate',
            'recipient_name' => 'Sam',
            'mode' => 'manual',
            'field_values' => [],
        ];
    }
}
