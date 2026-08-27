<?php

namespace App\Models;

use Database\Factories\AiGenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $application_id
 * @property string $kind
 * @property int|null $email_template_id
 * @property string $model
 * @property string $status
 * @property string|null $error
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property int $total_tokens
 * @property int $duration_ms
 * @property string|null $ip_address
 */
#[Fillable([
    'user_id', 'application_id', 'kind', 'email_template_id', 'model', 'status', 'error',
    'prompt_tokens', 'completion_tokens', 'total_tokens', 'duration_ms', 'ip_address',
])]
class AiGeneration extends Model
{
    /** @use HasFactory<AiGenerationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
