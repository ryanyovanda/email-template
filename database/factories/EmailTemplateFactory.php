<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use App\Services\Templates\TemplateParser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $html = <<<'HTML'
        <table><tr><td style="border-left:4px solid {{ accent }}">
          <p>Dear {{ recipient_name }},</p>
          <p>{{ intro }}</p>
          <p>Applying for {{ position }} at {{ company }}.</p>
          <div>{{# skills }}<span>{{ . }}</span>{{/ skills }}</div>
          <p>{{ full_name }} &middot; <a href="{{ portfolio_url }}">{{ portfolio_url }}</a></p>
        </td></tr></table>
        HTML;

        $name = fake()->unique()->company().' Layout';

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'accent_color' => '#E86A33',
            'html' => $html,
            'fields' => (new TemplateParser)->buildFields($html),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
