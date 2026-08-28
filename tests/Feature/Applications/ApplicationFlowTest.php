<?php

namespace Tests\Feature\Applications;

use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function userWithProfile(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        return $user;
    }

    public function test_a_user_without_a_profile_is_sent_to_onboarding(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('templates.index'))
            ->assertRedirect(route('applicant-profile.edit'));
    }

    public function test_the_gallery_only_lists_active_templates(): void
    {
        $active = EmailTemplate::factory()->create(['name' => 'Visible One']);
        EmailTemplate::factory()->hidden()->create(['name' => 'Hidden One']);

        $this->actingAs($this->userWithProfile())
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('templates/Index')
                ->has('templates', 1)
                ->where('templates.0.name', $active->name)
            );
    }

    public function test_choosing_a_template_creates_a_draft_and_opens_the_editor(): void
    {
        $user = $this->userWithProfile();
        $template = EmailTemplate::factory()->create();

        $response = $this->actingAs($user)->post(route('applications.store'), [
            'email_template_id' => $template->id,
            'title' => 'Application — Acme Corp',
        ]);

        $application = Application::sole();

        $this->assertSame($user->id, $application->user_id);
        $this->assertSame($template->id, $application->email_template_id);
        $response->assertRedirect(route('applications.edit', $application));
    }

    public function test_a_hidden_template_cannot_be_chosen(): void
    {
        $template = EmailTemplate::factory()->hidden()->create();

        $this->actingAs($this->userWithProfile())
            ->post(route('applications.store'), ['email_template_id' => $template->id])
            ->assertSessionHasErrors('email_template_id');

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_the_editor_exposes_only_user_facing_fields(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('applications.edit', $application))
            ->assertOk()
            ->assertInertia(function ($page) {
                $tokens = collect($page->toArray()['props']['template']['fields'])
                    ->pluck('token');

                $this->assertContains('intro', $tokens);
                $this->assertNotContains('full_name', $tokens, 'Profile tokens must not appear as inputs.');
                $this->assertNotContains('accent', $tokens, 'System tokens must not appear as inputs.');
            });
    }

    public function test_the_list_shows_only_the_users_own_applications(): void
    {
        $user = $this->userWithProfile();
        Application::factory()->for($user)->create(['title' => 'Mine']);
        Application::factory()->create(['title' => 'Someone Else\'s']);

        $this->actingAs($user)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('applications/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.title', 'Mine')
            );
    }

    public function test_an_application_is_named_by_its_company(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create([
            'title' => 'Untitled application',
            'company' => 'Acme Corp',
        ]);

        $this->actingAs($user)
            ->get(route('applications.index'))
            ->assertInertia(fn ($page) => $page
                ->where('applications.data.0.display_name', 'Acme Corp')
            );

        $this->assertSame('Acme Corp', $application->displayName());
    }

    public function test_a_draft_with_no_company_falls_back_to_its_label(): void
    {
        $user = $this->userWithProfile();
        Application::factory()->for($user)->create([
            'title' => 'Something I am still writing',
            'company' => null,
        ]);

        $this->actingAs($user)
            ->get(route('applications.index'))
            ->assertInertia(fn ($page) => $page
                ->where('applications.data.0.display_name', 'Something I am still writing')
            );
    }

    public function test_a_new_draft_starts_untitled_rather_than_named_after_the_template(): void
    {
        $user = $this->userWithProfile();
        $template = EmailTemplate::factory()->create(['name' => 'Grayscale Accent']);

        $this->actingAs($user)->post(route('applications.store'), [
            'email_template_id' => $template->id,
        ])->assertRedirect();

        $this->assertSame('Untitled application', Application::sole()->displayName());
    }

    public function test_the_download_filename_follows_the_company(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create(['company' => 'Acme Corp']);

        $this->actingAs($user)
            ->get(route('applications.download', $application))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="acme-corp.html"');
    }

    public function test_the_draft_label_may_be_left_empty(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create();

        $this->actingAs($user)->put(route('applications.update', $application), [
            'title' => '',
            'company' => 'Acme Corp',
            'mode' => 'manual',
            'field_values' => [],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Untitled application', $application->fresh()->title);
        $this->assertSame('Acme Corp', $application->fresh()->displayName());
    }

    public function test_a_new_draft_opens_on_the_ai_path(): void
    {
        $user = $this->userWithProfile();
        $template = EmailTemplate::factory()->create();

        $this->actingAs($user)->post(route('applications.store'), [
            'email_template_id' => $template->id,
        ]);

        $this->assertSame('ai', Application::sole()->mode);
    }

    public function test_the_role_details_are_exposed_as_correctable_fields(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create();

        // They are no longer a form the user fills in first, but they must still
        // reach the page so a misread company or contact name can be fixed.
        $this->actingAs($user)
            ->get(route('applications.edit', $application))
            ->assertOk()
            ->assertInertia(function ($page) {
                $tokens = collect($page->toArray()['props']['template']['fields'])
                    ->pluck('token');

                $this->assertContains('company', $tokens);
                $this->assertContains('recipient_name', $tokens);
            });
    }

    public function test_a_user_cannot_open_someone_elses_application(): void
    {
        $application = Application::factory()->create();

        $this->actingAs($this->userWithProfile())
            ->get(route('applications.edit', $application))
            ->assertForbidden();
    }

    public function test_saving_a_draft_stores_the_values_and_the_rendered_html(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create();

        $this->actingAs($user)->put(route('applications.update', $application), [
            'title' => 'Acme Corp — Senior Associate',
            'company' => 'Acme Corp',
            'position' => 'Senior Associate',
            'recipient_name' => 'Sam',
            'mode' => 'manual',
            'field_values' => ['intro' => 'I am applying for this role.', 'skills' => "TNA\nLMS"],
        ])->assertRedirect();

        $application->refresh();

        $this->assertSame('Acme Corp', $application->company);
        $this->assertSame('I am applying for this role.', $application->field_values['intro']);
        $this->assertStringContainsString('I am applying for this role.', (string) $application->rendered_html);
        $this->assertStringContainsString('<span>TNA</span>', (string) $application->rendered_html);
    }

    public function test_the_preview_endpoint_renders_without_saving(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create(['company' => 'Saved Co']);

        $response = $this->actingAs($user)->postJson(route('applications.render', $application), [
            'company' => 'Typed Co',
            'position' => 'Senior Associate',
            'field_values' => ['intro' => 'Live preview text.'],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('Live preview text.', $response->json('html'));
        $this->assertStringContainsString('Typed Co', $response->json('html'));
        $this->assertStringContainsString('Senior Associate', $response->json('subject'));

        $this->assertSame('Saved Co', $application->fresh()->company, 'Previewing must not persist anything.');
    }

    public function test_downloading_marks_the_application_as_copied(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('applications.download', $application))
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=utf-8');

        $this->assertNotNull($application->fresh()->last_copied_at);
    }

    public function test_a_user_can_delete_their_own_application(): void
    {
        $user = $this->userWithProfile();
        $application = Application::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect(route('applications.index'));

        $this->assertDatabaseCount('applications', 0);
    }
}
