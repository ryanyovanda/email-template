<?php

namespace Tests\Feature\Credits;

use App\Models\CreditTransaction;
use App\Models\Profile;
use App\Models\User;
use App\Services\Credits\CreditLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedCreditBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_balance_is_shared_with_every_page_for_the_sidebar(): void
    {
        config()->set('emailcv.credits.monthly_grant', 300);

        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        foreach (['dashboard', 'templates.index', 'applications.index'] as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertInertia(fn ($page) => $page->where('credits', 300));
        }
    }

    public function test_the_balance_reflects_spending(): void
    {
        config()->set('emailcv.credits.monthly_grant', 300);

        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        // Through the service, as every caller in the app does — writing to the
        // table directly would skip the monthly grant that has to land first.
        app(CreditLedger::class)->grant($user, CreditTransaction::ADMIN_ADJUSTMENT, -40);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('credits', 260));
    }

    public function test_a_guest_page_carries_no_balance(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('credits', null));
    }
}
