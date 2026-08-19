<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModerationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    public function test_an_admin_can_suspend_an_abusive_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.users.ban', $user), ['reason' => 'Scripted the AI endpoint.'])
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->banned_at);
        $this->assertSame('Scripted the AI endpoint.', $user->ban_reason);
    }

    public function test_suspending_requires_a_reason(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.users.ban', $user), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertNull($user->fresh()->banned_at);
    }

    public function test_a_suspended_user_is_signed_out_on_their_next_request(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['banned_at' => now(), 'ban_reason' => 'Abuse.'])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_an_admin_can_reinstate_an_account(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['banned_at' => now(), 'ban_reason' => 'Abuse.'])->save();

        $this->actingAs($this->admin())
            ->delete(route('admin.users.unban', $user))
            ->assertRedirect();

        $this->assertNull($user->fresh()->banned_at);
    }

    public function test_an_admin_cannot_suspend_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.ban', $admin), ['reason' => 'Oops.'])
            ->assertStatus(422);

        $this->assertNull($admin->fresh()->banned_at);
    }

    public function test_an_admin_can_change_a_users_monthly_ai_allowance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.users.limit', $user), ['ai_monthly_limit' => 3])
            ->assertRedirect();

        $this->assertSame(3, $user->fresh()->monthlyAiLimit());
    }

    public function test_clearing_the_allowance_restores_the_app_default(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['ai_monthly_limit' => 3])->save();

        $this->actingAs($this->admin())
            ->patch(route('admin.users.limit', $user), ['ai_monthly_limit' => null])
            ->assertRedirect();

        $this->assertSame(
            (int) config('emailcv.ai.monthly_limit'),
            $user->fresh()->monthlyAiLimit()
        );
    }
}
