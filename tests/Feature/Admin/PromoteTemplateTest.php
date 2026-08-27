<?php

namespace Tests\Feature\Admin;

use App\Models\CreditTransaction;
use App\Models\EmailTemplate;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function userTemplate(User $author, array $overrides = []): EmailTemplate
    {
        $template = EmailTemplate::factory()->create([
            'created_by' => $author->id,
            ...$overrides,
        ]);

        $template->forceFill([
            'origin' => 'user',
            'visibility' => 'private',
            'terms_accepted_at' => now(),
            'brief' => 'Something bold for a designer.',
            ...$overrides,
        ])->save();

        return $template->fresh();
    }

    public function test_an_admin_can_publish_a_user_design_to_the_shared_library(): void
    {
        $author = User::factory()->create();
        $template = $this->userTemplate($author);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.templates.promote', $template))
            ->assertRedirect();

        $template->refresh();

        $this->assertSame('global', $template->visibility);
        $this->assertTrue($template->is_active);
        $this->assertNotNull($template->promoted_at);
        $this->assertSame($admin->id, $template->promoted_by);
        // Authorship is kept for the record, not shown to other users.
        $this->assertSame($author->id, $template->created_by);
    }

    public function test_a_published_design_becomes_available_to_everyone(): void
    {
        $author = User::factory()->create();
        $template = $this->userTemplate($author);

        $stranger = User::factory()->create();
        Profile::factory()->for($stranger)->create();

        $this->actingAs($stranger)->get(route('templates.index'))
            ->assertInertia(fn ($page) => $page->where(
                'templates',
                fn ($templates) => ! collect($templates)->contains('id', $template->id)
            ));

        $this->actingAs($this->admin())->post(route('admin.templates.promote', $template));

        $this->actingAs($stranger)->get(route('templates.index'))
            ->assertInertia(fn ($page) => $page->where(
                'templates',
                fn ($templates) => collect($templates)
                    ->firstWhere('id', $template->id)['is_community'] === true
            ));

        $this->actingAs($stranger)
            ->post(route('applications.store'), ['email_template_id' => $template->id])
            ->assertRedirect();
    }

    public function test_a_design_without_recorded_consent_cannot_be_published(): void
    {
        $template = $this->userTemplate(User::factory()->create(), ['terms_accepted_at' => null]);

        $this->actingAs($this->admin())
            ->post(route('admin.templates.promote', $template))
            ->assertSessionHasErrors('template');

        $this->assertSame('private', $template->fresh()->visibility);
    }

    public function test_an_admin_can_withdraw_a_published_design(): void
    {
        $template = $this->userTemplate(User::factory()->create(), ['visibility' => 'global']);

        $this->actingAs($this->admin())
            ->delete(route('admin.templates.demote', $template))
            ->assertRedirect();

        $template->refresh();

        $this->assertSame('private', $template->visibility);
        $this->assertNull($template->promoted_at);
    }

    public function test_publishing_a_design_pays_its_author(): void
    {
        config()->set('emailcv.credits.promotion_reward', 50);

        $author = User::factory()->create();
        $before = $author->creditBalance();
        $template = $this->userTemplate($author);

        $this->actingAs($this->admin())
            ->post(route('admin.templates.promote', $template))
            ->assertRedirect();

        $this->assertSame($before + 50, $author->fresh()->creditBalance());

        $reward = CreditTransaction::where('reason', CreditTransaction::TEMPLATE_PROMOTED)->sole();
        $this->assertSame($template->id, $reward->email_template_id);
    }

    public function test_republishing_cannot_be_used_to_farm_credits(): void
    {
        config()->set('emailcv.credits.promotion_reward', 50);

        $author = User::factory()->create();
        $before = $author->creditBalance();
        $template = $this->userTemplate($author);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.templates.promote', $template));
        $this->actingAs($admin)->delete(route('admin.templates.demote', $template));
        $this->actingAs($admin)->post(route('admin.templates.promote', $template));

        $this->assertSame($before + 50, $author->fresh()->creditBalance());
        $this->assertSame(1, CreditTransaction::where('reason', CreditTransaction::TEMPLATE_PROMOTED)->count());
    }

    public function test_publishing_an_admin_authored_template_pays_nobody(): void
    {
        config()->set('emailcv.credits.promotion_reward', 50);

        $template = EmailTemplate::factory()->create();
        $template->forceFill(['origin' => 'system', 'visibility' => 'private'])->save();

        $this->actingAs($this->admin())
            ->post(route('admin.templates.promote', $template))
            ->assertRedirect();

        $this->assertDatabaseMissing('credit_transactions', [
            'reason' => CreditTransaction::TEMPLATE_PROMOTED,
        ]);
    }

    public function test_a_regular_user_cannot_publish_anything(): void
    {
        $author = User::factory()->create();
        $template = $this->userTemplate($author);

        // Not even the author of the design.
        $this->actingAs($author)
            ->post(route('admin.templates.promote', $template))
            ->assertForbidden();

        $this->assertSame('private', $template->fresh()->visibility);
    }

    public function test_the_admin_list_shows_who_asked_for_a_design_and_what_they_asked_for(): void
    {
        $author = User::factory()->create(['email' => 'designer@example.com']);
        $this->userTemplate($author);

        $this->actingAs($this->admin())
            ->get(route('admin.templates.index', ['filter' => 'pending']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('templates', 1)
                ->where('templates.0.author.email', 'designer@example.com')
                ->where('templates.0.brief', 'Something bold for a designer.')
                ->where('templates.0.terms_accepted', true)
                ->where('counts.pending', 1)
            );
    }

    public function test_seeded_templates_are_not_listed_as_user_made(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        $this->actingAs($this->admin())
            ->get(route('admin.templates.index', ['filter' => 'user']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('templates', 0));
    }
}
