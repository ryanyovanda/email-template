<?php

namespace Tests\Feature\Admin;

use App\Models\CreditTransaction;
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

    public function test_an_admin_can_add_credits(): void
    {
        $user = User::factory()->create();
        $before = $user->creditBalance();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.credits', $user), ['amount' => 150, 'reason' => 'Goodwill'])
            ->assertRedirect();

        $this->assertSame($before + 150, $user->fresh()->creditBalance());

        $entry = CreditTransaction::where('reason', CreditTransaction::ADMIN_ADJUSTMENT)->sole();

        $this->assertSame(150, $entry->amount);
        $this->assertSame('Goodwill', $entry->description);
        $this->assertSame($admin->id, $entry->created_by, 'An adjustment must record who made it.');
    }

    public function test_an_admin_can_take_credits_away(): void
    {
        $user = User::factory()->create();
        $before = $user->creditBalance();

        $this->actingAs($this->admin())
            ->post(route('admin.users.credits', $user), ['amount' => -100])
            ->assertRedirect();

        $this->assertSame($before - 100, $user->fresh()->creditBalance());
    }

    public function test_a_zero_adjustment_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.users.credits', $user), ['amount' => 0])
            ->assertSessionHasErrors('amount');
    }

    public function test_a_regular_user_cannot_adjust_credits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.users.credits', $user), ['amount' => 5000])
            ->assertForbidden();

        $this->assertDatabaseCount('credit_transactions', 0);
    }
}
