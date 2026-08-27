<?php

namespace Tests\Feature\Templates;

use App\Models\Profile;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $profile = []): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create($profile);

        return $user;
    }

    public function test_every_card_ships_a_rendered_preview(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        $this->actingAs($this->user())
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(function ($page) {
                $templates = $page->toArray()['props']['templates'];

                $this->assertNotEmpty($templates);

                foreach ($templates as $template) {
                    $this->assertNotEmpty(
                        $template['preview_html'],
                        "{$template['name']} shipped an empty preview."
                    );
                    $this->assertStringNotContainsString(
                        '{{',
                        $template['preview_html'],
                        "{$template['name']} left unresolved tokens in its preview."
                    );
                    $this->assertStringContainsString('<table', $template['preview_html']);
                }
            });
    }

    public function test_the_preview_uses_the_viewers_own_details(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        $user = $this->user([
            'full_name' => 'Fajira Zenitha Purnama',
            'location' => 'Bandung',
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertInertia(function ($page) {
                $previews = collect($page->toArray()['props']['templates'])->pluck('preview_html');

                $this->assertTrue(
                    $previews->every(fn (string $html): bool => str_contains($html, 'Fajira Zenitha Purnama')),
                    'Previews should show the viewer their own name.'
                );
            });
    }

    public function test_a_gap_in_the_profile_falls_back_to_sample_content(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        // No photo saved: the preview must still render an image rather than a
        // broken one, or the card looks like the template is at fault.
        $user = $this->user(['photo_url' => null]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertInertia(function ($page) {
                $neon = collect($page->toArray()['props']['templates'])->firstWhere('slug', 'neon-portfolio');

                $this->assertStringContainsString('<img', $neon['preview_html']);
                $this->assertStringNotContainsString('src=""', $neon['preview_html']);
            });
    }
}
