<?php

namespace Tests\Feature\Applications;

use App\Models\AiGeneration;
use App\Models\Application;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AiDraftTest extends TestCase
{
    use RefreshDatabase;

    private const JOB_POST = 'We are hiring a Finance, People and General Affairs Senior Associate in South Jakarta. You will own payroll, vendor management and employee onboarding for a research firm.';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.deepseek.key', 'test-key');
        config()->set('emailcv.ai.daily_limit', 5);
        config()->set('emailcv.ai.monthly_limit', 30);

        RateLimiter::clear('ai-draft:1');
    }

    private function fakeDeepSeek(?array $content = null): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode($content ?? [
                            'intro' => 'I am applying for the Senior Associate role.',
                            'skills' => ['TNA', 'Payroll', 'Vendor Management'],
                            'company' => 'Upsize Research',
                            'position' => 'Senior Associate',
                            'recipient_name' => 'Riko',
                        ]),
                    ],
                ]],
                'usage' => ['prompt_tokens' => 900, 'completion_tokens' => 150, 'total_tokens' => 1050],
            ]),
        ]);
    }

    private function setup_user(array $profileState = []): array
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create($profileState);
        $application = Application::factory()->for($user)->create([
            'company' => null,
            'position' => null,
            'recipient_name' => null,
        ]);

        return [$user, $application];
    }

    public function test_it_writes_the_fields_the_template_exposes(): void
    {
        $this->fakeDeepSeek();
        [$user, $application] = $this->setup_user();

        $response = $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => self::JOB_POST]
        );

        $response->assertOk();
        $this->assertSame('I am applying for the Senior Associate role.', $response->json('field_values.intro'));
        $this->assertStringContainsString('I am applying for the Senior Associate role.', $response->json('html'));
        $this->assertStringContainsString('<span>TNA</span>', $response->json('html'));

        $application->refresh();
        $this->assertSame('ai', $application->mode);
        $this->assertNotNull($application->last_generated_at);
    }

    public function test_it_lifts_the_role_details_onto_the_application(): void
    {
        $this->fakeDeepSeek();
        [$user, $application] = $this->setup_user();

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => self::JOB_POST]
        )->assertOk();

        $application->refresh();

        $this->assertSame('Upsize Research', $application->company);
        $this->assertSame('Riko', $application->recipient_name);
        $this->assertArrayNotHasKey('company', $application->field_values);
    }

    public function test_it_never_overwrites_details_the_user_typed(): void
    {
        $this->fakeDeepSeek();
        [$user, $application] = $this->setup_user();

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => self::JOB_POST, 'company' => 'My Typed Company']
        )->assertOk();

        $this->assertSame('My Typed Company', $application->fresh()->company);
    }

    public function test_it_records_the_generation_for_quota_and_auditing(): void
    {
        $this->fakeDeepSeek();
        [$user, $application] = $this->setup_user();

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => self::JOB_POST]
        )->assertOk();

        $generation = AiGeneration::sole();

        $this->assertSame($user->id, $generation->user_id);
        $this->assertSame('success', $generation->status);
        $this->assertSame(1050, $generation->total_tokens);
    }

    public function test_it_refuses_when_there_is_no_cv_text(): void
    {
        Http::fake();
        [$user, $application] = $this->setup_user(['cv_text' => null, 'cv_parse_status' => 'empty']);

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => self::JOB_POST]
        )->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_it_requires_a_substantial_job_posting(): void
    {
        Http::fake();
        [$user, $application] = $this->setup_user();

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => 'Hiring.']
        )->assertStatus(422)->assertJsonValidationErrors('job_post');

        Http::assertNothingSent();
    }

    public function test_it_stops_at_the_daily_quota(): void
    {
        config()->set('emailcv.ai.daily_limit', 2);
        config()->set('emailcv.ai.rate_limit_per_minute', 100);

        $this->fakeDeepSeek();
        [$user, $application] = $this->setup_user();

        AiGeneration::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'success',
        ]);

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => self::JOB_POST]
        )->assertStatus(429);

        Http::assertNothingSent();
    }

    public function test_an_admin_can_zero_out_a_users_allowance(): void
    {
        $this->fakeDeepSeek();
        [$user, $application] = $this->setup_user();
        $user->forceFill(['ai_monthly_limit' => 0])->save();

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => self::JOB_POST]
        )->assertStatus(429);

        Http::assertNothingSent();
    }

    public function test_a_failing_api_is_reported_and_logged_without_consuming_quota(): void
    {
        Http::fake(['*/chat/completions' => Http::response(['error' => ['message' => 'boom']], 500)]);
        [$user, $application] = $this->setup_user();

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $application),
            ['job_post' => self::JOB_POST]
        )->assertStatus(502);

        $generation = AiGeneration::sole();

        $this->assertSame('failed', $generation->status);
        $this->assertSame(0, $user->fresh()->aiGenerationsThisMonth(), 'Failed calls must not eat the allowance.');
    }

    public function test_a_user_cannot_generate_on_someone_elses_application(): void
    {
        Http::fake();
        [$user] = $this->setup_user();
        $other = Application::factory()->create();

        $this->actingAs($user)->postJson(
            route('applications.ai-draft', $other),
            ['job_post' => self::JOB_POST]
        )->assertForbidden();

        Http::assertNothingSent();
    }
}
