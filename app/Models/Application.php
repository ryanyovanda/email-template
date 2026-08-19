<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $email_template_id
 * @property string $title
 * @property string|null $company
 * @property string|null $position
 * @property string|null $recipient_name
 * @property string|null $job_post
 * @property string $mode
 * @property array<string, mixed>|null $field_values
 * @property string|null $rendered_html
 * @property CarbonImmutable|null $last_generated_at
 * @property CarbonImmutable|null $last_copied_at
 */
#[Fillable([
    'email_template_id', 'title', 'company', 'position', 'recipient_name',
    'job_post', 'mode', 'field_values', 'rendered_html',
    'last_generated_at', 'last_copied_at',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

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

    /**
     * @return HasMany<AiGeneration, $this>
     */
    public function generations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    protected function casts(): array
    {
        return [
            'field_values' => 'array',
            'last_generated_at' => 'immutable_datetime',
            'last_copied_at' => 'immutable_datetime',
        ];
    }
}
