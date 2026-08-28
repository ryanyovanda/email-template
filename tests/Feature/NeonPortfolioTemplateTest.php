<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Profile;
use App\Models\User;
use App\Services\Templates\TemplateLinter;
use App\Services\Templates\TemplateRenderer;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The seeded templates ship to real recruiters, so they are held to the same
 * compatibility rules the CMS enforces on admin-authored ones.
 */
class NeonPortfolioTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EmailTemplateSeeder::class);
    }

    private function render(string $slug): string
    {
        $template = EmailTemplate::where('slug', $slug)->sole();

        $profile = Profile::factory()->make([
            'full_name' => 'Jane Doe',
            'portfolio_url' => 'https://janedoe.example.com',
        ]);

        $values = [];

        foreach ($template->userFields() as $field) {
            $values[$field['token']] = $field['type'] === 'list'
                ? ['Figma', 'After Effects', 'Art Direction']
                : 'Sample copy for '.$field['token'];
        }

        return app(TemplateRenderer::class)->render(
            $template,
            $profile,
            $values,
            new Application(['company' => 'Studio Kolektif', 'position' => 'Senior Brand Designer', 'recipient_name' => 'Sam'])
        );
    }

    public static function seededTemplates(): array
    {
        return [
            'grayscale accent' => ['grayscale-accent'],
            'clean letter' => ['clean-letter'],
            'neon portfolio' => ['neon-portfolio'],
            'split profile' => ['split-profile'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    #[DataProvider('seededTemplates')]
    public function test_every_seeded_template_passes_the_compatibility_linter(string $slug): void
    {
        $template = EmailTemplate::where('slug', $slug)->sole();

        $this->assertSame([], (new TemplateLinter)->lint($template->html));
    }

    /**
     * @return array<string, array{string}>
     */
    #[DataProvider('seededTemplates')]
    public function test_every_seeded_template_renders_with_no_tokens_left_behind(string $slug): void
    {
        $this->assertDoesNotMatchRegularExpression('/\{\{/', $this->render($slug));
    }

    public function test_the_neon_template_falls_back_to_solid_colour_where_it_uses_a_gradient(): void
    {
        $html = $this->render('neon-portfolio');

        // Outlook ignores linear-gradient, so every gradient needs a bgcolor.
        preg_match_all('/<td[^>]*linear-gradient[^>]*>/i', $html, $matches);

        $this->assertNotEmpty($matches[0], 'The template should use gradients.');

        foreach ($matches[0] as $cell) {
            $this->assertStringContainsString('bgcolor=', $cell);
        }
    }

    public function test_the_neon_template_derives_its_whole_palette_from_the_accent(): void
    {
        $template = EmailTemplate::where('slug', 'neon-portfolio')->sole();
        $template->accent_color = '#0AA36B';
        $template->save();

        $html = $this->render('neon-portfolio');

        $this->assertStringContainsString('#0AA36B', $html);
        $this->assertStringNotContainsString('#7C3AED', $html, 'No trace of the old accent should remain.');
    }

    public function test_a_new_draft_starts_with_the_templates_default_copy(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();
        $template = EmailTemplate::where('slug', 'neon-portfolio')->sole();

        $this->actingAs($user)
            ->post(route('applications.store'), ['email_template_id' => $template->id])
            ->assertRedirect();

        $this->assertSame('See my portfolio', Application::sole()->field_values['cta_label']);
    }

    public function test_palette_tokens_are_not_offered_as_form_fields(): void
    {
        $tokens = collect(EmailTemplate::where('slug', 'neon-portfolio')->sole()->userFields())
            ->pluck('token');

        foreach (['accent', 'accent_alt', 'accent_dark', 'accent_soft', 'accent_contrast'] as $token) {
            $this->assertNotContains($token, $tokens);
        }
    }
}
