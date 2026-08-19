<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Services\Cv\CvTextExtractor;
use App\Services\Uploads\LocalUploader;
use App\Services\Uploads\Uploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->app->instance(Uploader::class, new LocalUploader);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'full_name' => 'Fajira Zenitha Purnama',
            'headline' => 'Learning & Development Specialist',
            'contact_email' => 'fajira@example.com',
            'phone' => '0851-5648-0171',
            'location' => 'Central Jakarta',
            ...$overrides,
        ];
    }

    public function test_the_profile_page_renders_for_a_new_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('applicant-profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Onboarding')
                ->where('isFirstRun', true)
                ->where('profile', null)
            );
    }

    public function test_the_profile_page_renders_saved_details(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['full_name' => 'Fajira Zenitha Purnama']);

        $this->actingAs($user)
            ->get(route('applicant-profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('isFirstRun', false)
                ->where('profile.full_name', 'Fajira Zenitha Purnama')
            );
    }

    public function test_a_new_user_can_save_their_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('applicant-profile.update'), $this->payload())
            ->assertRedirect(route('applicant-profile.edit'));

        $profile = Profile::sole();

        $this->assertSame($user->id, $profile->user_id);
        $this->assertSame('Fajira Zenitha Purnama', $profile->full_name);
        $this->assertNotNull($user->fresh()->onboarded_at);
    }

    public function test_a_name_and_contact_email_are_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('applicant-profile.update'), ['full_name' => '', 'contact_email' => 'nope'])
            ->assertSessionHasErrors(['full_name', 'contact_email']);

        $this->assertDatabaseCount('profiles', 0);
    }

    public function test_a_photo_upload_is_stored_and_linked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(
            route('applicant-profile.update'),
            $this->payload(['photo' => UploadedFile::fake()->image('me.jpg', 400, 400)])
        )->assertRedirect();

        $profile = Profile::sole();

        $this->assertNotNull($profile->photo_url);
        Storage::disk('public')->assertExists($profile->photo_public_id);
    }

    public function test_an_oversized_photo_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->post(
            route('applicant-profile.update'),
            $this->payload(['photo' => UploadedFile::fake()->image('huge.jpg')->size(9000)])
        )->assertSessionHasErrors('photo');
    }

    public function test_an_executable_disguised_as_a_cv_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->post(
            route('applicant-profile.update'),
            $this->payload(['cv' => UploadedFile::fake()->create('cv.php', 20, 'application/x-php')])
        )->assertSessionHasErrors('cv');

        $this->assertDatabaseCount('profiles', 0);
    }

    public function test_a_cv_we_cannot_read_is_flagged_so_the_user_can_paste_the_text(): void
    {
        $user = User::factory()->create();

        // A PDF with no extractable text stands in for a scanned CV.
        $this->actingAs($user)->post(
            route('applicant-profile.update'),
            $this->payload(['cv' => UploadedFile::fake()->create('scan.pdf', 40, 'application/pdf')])
        )->assertRedirect();

        $profile = Profile::sole();

        $this->assertContains($profile->cv_parse_status, [
            CvTextExtractor::STATUS_EMPTY,
            CvTextExtractor::STATUS_FAILED,
        ]);
        $this->assertFalse($profile->hasUsableCvText());
        $this->assertNotNull($profile->cv_url, 'The file is still stored for attaching in Gmail.');
    }

    public function test_a_user_can_paste_their_cv_text_by_hand(): void
    {
        $user = User::factory()->create();
        $text = str_repeat('Managed corporate L&D projects for PT KAI. ', 12);

        $this->actingAs($user)->post(
            route('applicant-profile.update'),
            $this->payload(['cv_text' => $text])
        )->assertRedirect();

        $profile = Profile::sole();

        $this->assertTrue($profile->hasUsableCvText());
        $this->assertSame(CvTextExtractor::STATUS_PARSED, $profile->cv_parse_status);
    }

    public function test_saving_and_continuing_goes_to_the_template_gallery(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('applicant-profile.update'), $this->payload(['continue' => true]))
            ->assertRedirect(route('templates.index'));
    }

    public function test_updating_a_profile_does_not_create_a_second_one(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('applicant-profile.update'), $this->payload(['full_name' => 'New Name']))
            ->assertRedirect();

        $this->assertDatabaseCount('profiles', 1);
        $this->assertSame('New Name', $user->fresh()->profile->full_name);
    }
}
