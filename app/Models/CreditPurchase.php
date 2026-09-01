<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One credit purchase, from invoice creation to payment. Credits reach the
 * ledger only when status becomes 'paid', and credit_transaction_id being set
 * is what proves that has already happened, so a repeated webhook is a no-op.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $credit_package_id
 * @property int $credits
 * @property int $amount
 * @property string $currency
 * @property string $status
 * @property string $external_id
 * @property string|null $xendit_invoice_id
 * @property string|null $invoice_url
 * @property int|null $credit_transaction_id
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'user_id', 'credit_package_id', 'credits', 'amount', 'currency',
    'status', 'external_id', 'xendit_invoice_id', 'invoice_url',
    'credit_transaction_id', 'paid_at',
])]
class CreditPurchase extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CreditPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class, 'credit_package_id');
    }

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
