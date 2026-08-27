<?php

namespace Database\Factories;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => 300,
            'reason' => CreditTransaction::MONTHLY_GRANT,
            'period' => now()->format('Y-m'),
        ];
    }

    public function spend(int $amount = 10, string $reason = CreditTransaction::APPLICATION_DRAFT): static
    {
        return $this->state(fn (): array => [
            'amount' => -abs($amount),
            'reason' => $reason,
            'period' => null,
        ]);
    }
}
