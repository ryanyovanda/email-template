<?php

namespace Tests\Feature\Templates;

use App\Models\CreditTransaction;
use App\Models\EmailTemplate;
use App\Models\Profile;
use App\Models\User;
use App\Services\Templates\TemplateGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('emailcv.credits.prices.template_design', 30);
        config()->set('emailcv.credits.monthly_grant', 300);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        return $user;
    }

    private function validHtml(): string
    {
        return '<table role="presentation" width="100%"><tr>'
            .'<td style="background-color:{{ accent }}; color:{{ accent_contrast }}">'
            .'<h1>{{ full_name }}</h1></td></tr><tr>'
            .'<td style="background-color:#ffffff"><p>Dear {{ recipient_name }},</p>'
            .'<p>{{ intro_paragraph }}</p>'
            .'<p>Applying for {{ position }} at {{ company }}.</p>'
            .'<p>{{ body_paragraph }}</p>'
            .'<p>{{ closing_paragraph }}</p></td></tr></table>';
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'My Letter',
            'description' => 'A plain letter.',
            'accent_color' => '#2F5D8C',
            'html' => $this->validHtml(),
            'accept_terms' => true,
            ...$overrides,
        ];
    }

    public function test_the_builder_page_ships_the_guide_examples_and_token_reference(): void
    {
        $this->actingAs($this->user())
            ->get(route('templates.build'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('templates/Build')
                ->has('guide', 7)
                ->has('tokens', 4)
                ->has('examples', 3)
                ->has('starter.html')
                ->has('terms')
                ->where('maxBytes', 60000)
            );
    }

    public function test_every_shipped_example_is_valid_as_it_stands(): void
    {
        $user = $this->user();

        foreach (app(TemplateGuide::class)->examples() as $example) {
            $this->actingAs($user)
                ->post(route('templates.build.store'), $this->payload([
                    'name' => $example['name'],
                    'accent_color' => $example['accent_color'],
                    'html' => $example['html'],
                ]))
                ->assertSessionHasNoErrors()
                ->assertRedirect();
        }

        $this->assertSame(3, EmailTemplate::where('created_by', $user->id)->count());
    }

    public function test_a_hand_written_template_is_private_marked_and_free(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('templates.build.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $template = EmailTemplate::where('created_by', $user->id)->sole();

        $this->assertSame(EmailTemplate::ORIGIN_HAND_WRITTEN, $template->origin);
        $this->assertTrue($template->isHandWritten());
        $this->assertTrue($template->isUserMade());
        $this->assertSame('private', $template->visibility);
        $this->assertNotNull($template->terms_accepted_at);
        $this->assertNotEmpty($template->fields);

        // Nothing was charged, and no generation was recorded.
        $this->assertDatabaseMissing('credit_transactions', [
            'user_id' => $user->id,
            'reason' => CreditTransaction::TEMPLATE_DESIGN,
        ]);
        $this->assertDatabaseCount('ai_generations', 0);
    }

    public function test_it_saves_with_no_credits_at_all(): void
    {
        $user = $this->user();

        config()->set('emailcv.credits.monthly_grant', 0);

        $this->actingAs($user)
            ->post(route('templates.build.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('email_templates', 1);
    }

    public function test_the_stored_html_is_the_sanitised_html(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('templates.build.store'), $this->payload([
                'html' => '<script>alert(1)</script>'
                    .'<style>.x{color:red}</style>'
                    .str_replace('<h1>', '<h1 onclick="steal()">', $this->validHtml()),
            ]))
            ->assertSessionHasNoErrors();

        $html = EmailTemplate::where('created_by', $user->id)->sole()->html;

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString('{{ full_name }}', $html);
    }

    public function test_it_rejects_html_that_is_missing_a_required_token(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('templates.build.store'), $this->payload([
                'html' => str_replace('{{ position }}', 'a role', $this->validHtml()),
            ]))
            ->assertSessionHasErrors('html');

        $this->assertDatabaseCount('email_templates', 0);
    }

    public function test_it_rejects_html_with_too_few_things_to_fill_in(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('templates.build.store'), $this->payload([
                'html' => '<table role="presentation"><tr>'
                    .'<td style="background-color:{{ accent }}">{{ full_name }}</td></tr>'
                    .'<tr><td style="background-color:#fff">Dear {{ recipient_name }}, '
                    .'about {{ position }} at {{ company }}. {{ intro_paragraph }}</td></tr></table>',
            ]))
            ->assertSessionHasErrors('html');
    }

    public function test_it_rejects_a_block_that_would_repeat_the_same_value(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('templates.build.store'), $this->payload([
                'html' => str_replace(
                    '<p>{{ closing_paragraph }}</p>',
                    '<p>{{ closing_paragraph }}</p>{{# projects }}<p>{{ project_name }} {{ . }}</p>{{/ projects }}',
                    $this->validHtml(),
                ),
            ]))
            ->assertSessionHasErrors('html');
    }

    public function test_the_terms_have_to_be_accepted(): void
    {
        $this->actingAs($this->user())
            ->post(route('templates.build.store'), $this->payload(['accept_terms' => false]))
            ->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseCount('email_templates', 0);
    }

    public function test_analyse_returns_a_preview_the_detected_fields_and_what_was_stripped(): void
    {
        $response = $this->actingAs($this->user())
            ->postJson(route('templates.analyse'), [
                'html' => '<style>.x{color:red}</style>'.$this->validHtml(),
                'accent_color' => '#2F5D8C',
            ])
            ->assertOk();

        $response->assertJsonPath('problems', []);
        $this->assertNotEmpty($response->json('removed'));
        $this->assertStringContainsString('table', $response->json('preview'));

        $tokens = array_column($response->json('fields'), 'token');
        $this->assertContains('intro_paragraph', $tokens);
        $this->assertContains('full_name', $tokens);
    }

    public function test_analyse_explains_what_is_wrong_without_saving_anything(): void
    {
        $response = $this->actingAs($this->user())
            ->postJson(route('templates.analyse'), [
                'html' => '<table role="presentation"><tr><td>Nothing useful here</td></tr></table>',
            ])
            ->assertOk();

        $this->assertNotEmpty($response->json('problems'));
        $this->assertDatabaseCount('email_templates', 0);
    }

    public function test_the_builder_is_closed_to_guests(): void
    {
        $this->get(route('templates.build'))->assertRedirect(route('login'));
        $this->post(route('templates.build.store'), $this->payload())->assertRedirect(route('login'));
        $this->postJson(route('templates.analyse'), ['html' => $this->validHtml()])->assertUnauthorized();
    }
}
