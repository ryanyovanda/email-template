<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CreditTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One movement of credits. Balances are derived by summing these, never stored,
 * so a spend can always be traced back to what it bought.
 *
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property string $reason
 * @property string|null $description
 * @property string|null $period
 * @property int|null $ai_generation_id
 * @property int|null $email_template_id
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'user_id', 'amount', 'reason', 'description', 'period',
    'ai_generation_id', 'email_template_id', 'created_by',
])]
class CreditTransaction extends Model
{
    /** @use HasFactory<CreditTransactionFactory> */
    use HasFactory;

    public const MONTHLY_GRANT = 'monthly_grant';

    public const APPLICATION_DRAFT = 'application_draft';

    public const TEMPLATE_DESIGN = 'template_design';

    public const TEMPLATE_PROMOTED = 'template_promoted';

    public const ADMIN_ADJUSTMENT = 'admin_adjustment';

    public const PURCHASE = 'purchase';

    /**
     * Human wording for the ledger, so a user is never shown a raw slug.
     */
    public function label(): string
    {
        return match ($this->reason) {
            self::MONTHLY_GRANT => 'Monthly credits',
            self::APPLICATION_DRAFT => 'AI application draft',
            self::TEMPLATE_DESIGN => 'AI template design',
            self::TEMPLATE_PROMOTED => 'Template published to the library',
            self::ADMIN_ADJUSTMENT => 'Adjusted by an administrator',
            self::PURCHASE => 'Credits purchased',
            default => ucfirst(str_replace('_', ' ', $this->reason)),
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<EmailTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }
}
