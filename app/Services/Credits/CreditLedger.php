<?php

namespace App\Services\Credits;

use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Settings\Settings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The credit balance is the sum of a user's ledger rows rather than a stored
 * number, so no movement can be lost and every spend is traceable to what it
 * bought.
 */
class CreditLedger
{
    public function __construct(private Settings $settings) {}

    /**
     * Current balance, after making sure this month's allowance has landed.
     */
    public function balance(User $user): int
    {
        $this->grantMonthlyAllowance($user);

        return $this->rawBalance($user);
    }

    public function priceOf(string $reason): int
    {
        return $this->settings->priceOf($reason);
    }

    public function canAfford(User $user, string $reason): bool
    {
        return $this->balance($user) >= $this->priceOf($reason);
    }

    /**
     * Take credits for something the user has already received.
     *
     * Spending happens after the work succeeds, so a failed generation costs
     * nothing. The balance is re-checked inside the transaction because the
     * check that gated the request happened before a call that takes seconds.
     *
     * @param  array<string, mixed>  $meta
     *
     * @throws InsufficientCreditsException
     */
    public function spend(User $user, string $reason, array $meta = []): CreditTransaction
    {
        $price = $this->priceOf($reason);

        $this->grantMonthlyAllowance($user);

        return DB::transaction(function () use ($user, $reason, $price, $meta): CreditTransaction {
            $balance = $this->rawBalance($user, lock: true);

            if ($balance < $price) {
                throw new InsufficientCreditsException($price, $balance);
            }

            return $this->record($user, -$price, $reason, $meta);
        });
    }

    /**
     * Add or remove credits outside of a purchase.
     *
     * The allowance is settled first so that an adjustment made before a user's
     * first visit is not silently undone by the top-up that follows it.
     *
     * @param  array<string, mixed>  $meta
     */
    public function grant(User $user, string $reason, int $amount, array $meta = []): CreditTransaction
    {
        $this->grantMonthlyAllowance($user);

        return $this->record($user, $amount, $reason, $meta);
    }

    /**
     * Reward the author of a template an admin has published. Guarded so that
     * unpublishing and republishing cannot be used to farm credits.
     */
    public function rewardPromotion(User $author, int $templateId): ?CreditTransaction
    {
        $alreadyRewarded = CreditTransaction::query()
            ->where('reason', CreditTransaction::TEMPLATE_PROMOTED)
            ->where('email_template_id', $templateId)
            ->exists();

        if ($alreadyRewarded) {
            return null;
        }

        return $this->grant(
            $author,
            CreditTransaction::TEMPLATE_PROMOTED,
            $this->settings->promotionReward(),
            ['email_template_id' => $templateId],
        );
    }

    /**
     * Tops the balance up to the monthly allowance the first time a user is seen
     * in a calendar month.
     *
     * This is lazy rather than scheduled because no scheduler runs in
     * production; the unique index on (user_id, reason, period) is what makes it
     * safe to attempt on every request.
     */
    public function grantMonthlyAllowance(User $user): ?CreditTransaction
    {
        $period = now()->format('Y-m');

        $alreadyGranted = CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('reason', CreditTransaction::MONTHLY_GRANT)
            ->where('period', $period)
            ->exists();

        if ($alreadyGranted) {
            return null;
        }

        $allowance = $this->settings->monthlyGrant();
        $topUp = $allowance - $this->rawBalance($user);

        if ($topUp <= 0) {
            // Already above the allowance from rewards or an admin adjustment;
            // record a zero row so the month is not reconsidered on every call.
            $topUp = 0;
        }

        try {
            return $this->record($user, $topUp, CreditTransaction::MONTHLY_GRANT, [
                'period' => $period,
                'description' => 'Monthly allowance',
            ]);
        } catch (QueryException) {
            // Another request granted the same period first; the unique index
            // did its job and there is nothing more to do.
            return null;
        }
    }

    /**
     * @return array<int, CreditTransaction>
     */
    public function history(User $user, int $limit = 20): array
    {
        return CreditTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * The only place a ledger row is written. Kept private so that granting the
     * monthly allowance cannot recurse into itself.
     *
     * @param  array<string, mixed>  $meta
     */
    private function record(User $user, int $amount, string $reason, array $meta = []): CreditTransaction
    {
        return CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'reason' => $reason,
            ...$meta,
        ]);
    }

    private function rawBalance(User $user, bool $lock = false): int
    {
        $query = CreditTransaction::query()->where('user_id', $user->id);

        if ($lock) {
            $query->lockForUpdate();
        }

        return (int) $query->sum('amount');
    }
}
