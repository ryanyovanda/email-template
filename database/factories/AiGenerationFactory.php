<?php

namespace Database\Factories;

use App\Models\AiGeneration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGeneration>
 */
class AiGenerationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'application_id' => null,
            'model' => 'deepseek-chat',
            'status' => 'success',
            'prompt_tokens' => 900,
            'completion_tokens' => 150,
            'total_tokens' => 1050,
            'duration_ms' => 4200,
            'ip_address' => '127.0.0.1',
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'error' => 'The AI service is unavailable right now.',
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ]);
    }
}
