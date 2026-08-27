<?php

namespace Tests\Feature\Credits;

use App\Models\CreditTransaction;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Credits\CreditLedger;
use App\Services\Credits\InsufficientCreditsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLedgerTest extends TestCase
{
    use RefreshDatabase;

    private CreditLedger $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('emailcv.credits.monthly_grant', 300);
        config()->set('emailcv.credits.prices.application_draft', 10);
        config()->set('emailcv.credits.prices.template_design', 30);

        $this->ledger = app(CreditLedger::class);
    }

    public function test_a_new_account_starts_with_the_monthly_allowance(): void
    {
        $this->assertSame(300, $this->ledger->balance(User::factory()->create()));
    }

    public function test_the_allowance_is_granted_once_per_month(): void
    {
        $user = User::factory()->create();

        $this->ledger->balance($user);
        $this->ledger->balance($user);
        $this->ledger->balance($user);

        $this->assertSame(1, CreditTransaction::where('reason', CreditTransaction::MONTHLY_GRANT)->count());
        $this->assertSame(300, $this->ledger->balance($user));
    }

    public function test_a_new_month_tops_the_balance_back_up(): void
    {
        $user = User::factory()->create();

        $this->ledger->balance($user);
        $this->ledger->spend($user, 'application_draft');
        $this->assertSame(290, $this->ledger->balance($user));

        $this->travelTo(now()->addMonthNoOverflow()->startOfMonth());

        $this->assertSame(300, $this->ledger->balance($user), 'A new month should top up to the allowance.');
        $this->assertSame(2, CreditTransaction::where('reason', CreditTransaction::MONTHLY_GRANT)->count());
    }

    public function test_the_top_up_does_not_stack_a_balance_beyond_the_allowance(): void
    {
        $user = User::factory()->create();

        $this->ledger->balance($user);
        $this->travelTo(now()->addMonthNoOverflow()->startOfMonth());

        // Untouched for a month, so the balance stays at the allowance rather
        // than doubling.
        $this->assertSame(300, $this->ledger->balance($user));
    }

    public function test_a_reward_survives_the_monthly_top_up(): void
    {
        $user = User::factory()->create();

        $this->ledger->balance($user);
        $this->ledger->grant($user, CreditTransaction::TEMPLATE_PROMOTED, 50);
        $this->assertSame(350, $this->ledger->balance($user));

        $this->travelTo(now()->addMonthNoOverflow()->startOfMonth());

        $this->assertSame(350, $this->ledger->balance($user), 'Earned credits must not be clawed back.');
    }

    public function test_spending_reduces_the_balance_by_the_price(): void
    {
        $user = User::factory()->create();

        $this->ledger->spend($user, 'template_design');

        $this->assertSame(270, $this->ledger->balance($user));
    }

    public function test_spending_beyond_the_balance_is_refused(): void
    {
        $user = User::factory()->create();

        $this->ledger->grant($user, CreditTransaction::ADMIN_ADJUSTMENT, -295);
        $this->assertSame(5, $this->ledger->balance($user));

        $this->expectException(InsufficientCreditsException::class);

        $this->ledger->spend($user, 'application_draft');
    }

    public function test_a_refused_spend_leaves_no_trace(): void
    {
        $user = User::factory()->create();
        $this->ledger->grant($user, CreditTransaction::ADMIN_ADJUSTMENT, -295);

        try {
            $this->ledger->spend($user, 'application_draft');
        } catch (InsufficientCreditsException) {
            // expected
        }

        $this->assertSame(5, $this->ledger->balance($user));
        $this->assertDatabaseMissing('credit_transactions', ['reason' => 'application_draft']);
    }

    public function test_a_promotion_is_rewarded_only_once_per_template(): void
    {
        config()->set('emailcv.credits.promotion_reward', 50);

        $user = User::factory()->create();
        $this->ledger->balance($user);
        $template = EmailTemplate::factory()->create();

        $first = $this->ledger->rewardPromotion($user, $template->id);
        $second = $this->ledger->rewardPromotion($user, $template->id);

        $this->assertNotNull($first);
        $this->assertNull($second, 'Re-publishing must not pay out twice.');
        $this->assertSame(350, $this->ledger->balance($user));
    }

    public function test_the_balance_is_the_sum_of_the_ledger(): void
    {
        $user = User::factory()->create();

        $this->ledger->balance($user);
        $this->ledger->spend($user, 'application_draft');
        $this->ledger->spend($user, 'template_design');
        $this->ledger->grant($user, CreditTransaction::ADMIN_ADJUSTMENT, 25);

        $this->assertSame(
            (int) CreditTransaction::where('user_id', $user->id)->sum('amount'),
            $this->ledger->balance($user),
        );
        $this->assertSame(285, $this->ledger->balance($user));
    }

    public function test_deleting_a_user_removes_their_ledger(): void
    {
        $user = User::factory()->create();
        $this->ledger->balance($user);

        $user->delete();

        $this->assertDatabaseCount('credit_transactions', 0);
    }
}
