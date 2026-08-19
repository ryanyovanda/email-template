<?php

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Bold Header',
            'description' => 'A punchy layout.',
            'accent_color' => '#2563EB',
            'html' => '<table><tr><td>Dear {{ recipient_name }}, {{ intro }} — {{ full_name }}</td></tr></table>',
            'is_active' => true,
            'sort_order' => 5,
            ...$overrides,
        ];
    }

    public function test_an_admin_can_add_a_template_and_the_form_is_derived_from_its_tokens(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.templates.store'), $this->payload())
            ->assertRedirect();

        $template = EmailTemplate::sole();
        $tokens = collect($template->fields)->pluck('token');

        $this->assertSame('Bold Header', $template->name);
        $this->assertNotEmpty($template->slug);
        $this->assertTrue($tokens->contains('intro'));
        $this->assertTrue($tokens->contains('full_name'));
        $this->assertSame(['intro', 'recipient_name'], collect($template->userFields())->pluck('token')->sort()->values()->all());
    }

    public function test_a_new_template_appears_in_the_user_gallery(): void
    {
        $this->actingAs($this->admin())->post(route('admin.templates.store'), $this->payload());

        $this->assertTrue(EmailTemplate::active()->where('name', 'Bold Header')->exists());
    }

    public function test_editing_the_html_updates_the_detected_fields(): void
    {
        $template = EmailTemplate::factory()->create();

        $this->actingAs($this->admin())->put(
            route('admin.templates.update', $template),
            $this->payload([
                'slug' => $template->slug,
                'html' => '<table><tr><td>{{ opening }}{{# highlights }}{{ . }}{{/ highlights }}</td></tr></table>',
            ])
        )->assertRedirect();

        $fields = collect($template->fresh()->fields)->keyBy('token');

        $this->assertTrue($fields->has('opening'));
        $this->assertSame('list', $fields['highlights']['type']);
        $this->assertFalse($fields->has('intro'), 'Tokens removed from the HTML must drop out of the form.');
    }

    public function test_admin_customisations_survive_an_html_edit(): void
    {
        $template = EmailTemplate::factory()->create();
        $fields = $template->fields;
        $fields[] = ['token' => 'intro', 'label' => 'My custom label', 'type' => 'textarea', 'ai' => false, 'required' => true, 'help' => 'Keep it short.'];

        $this->actingAs($this->admin())->put(
            route('admin.templates.update', $template),
            $this->payload(['slug' => $template->slug, 'fields' => $fields])
        )->assertRedirect();

        $intro = collect($template->fresh()->fields)->firstWhere('token', 'intro');

        $this->assertSame('My custom label', $intro['label']);
        $this->assertFalse($intro['ai']);
    }

    public function test_the_analyse_endpoint_returns_fields_warnings_and_a_sample_render(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('admin.templates.analyse'), [
            'html' => '<div style="display:flex"><script>x</script>{{ intro }}</div>',
            'accent_color' => '#E86A33',
        ]);

        $response->assertOk();

        $this->assertSame('intro', $response->json('fields.0.token'));

        $warnings = collect($response->json('warnings'));
        $this->assertTrue($warnings->contains(fn ($w) => str_contains($w['message'], 'flexbox')));
        $this->assertTrue($warnings->contains(fn ($w) => $w['level'] === 'error' && str_contains($w['message'], 'Scripts')));

        $this->assertStringContainsString('Sample paragraph', $response->json('html'));
    }

    public function test_hiding_a_template_removes_it_from_the_gallery_without_deleting_it(): void
    {
        $template = EmailTemplate::factory()->create();

        $this->actingAs($this->admin())->put(
            route('admin.templates.update', $template),
            $this->payload(['slug' => $template->slug, 'is_active' => false])
        )->assertRedirect();

        $this->assertFalse($template->fresh()->is_active);
        $this->assertSame(0, EmailTemplate::active()->count());
    }

    public function test_deleting_a_template_leaves_existing_drafts_intact(): void
    {
        $template = EmailTemplate::factory()->create();
        $application = Application::factory()->create(['email_template_id' => $template->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.templates.destroy', $template))
            ->assertRedirect(route('admin.templates.index'));

        $this->assertDatabaseCount('email_templates', 0);
        $this->assertNull($application->fresh()->email_template_id);
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_a_regular_user_cannot_add_a_template(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.templates.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('email_templates', 0);
    }
}
