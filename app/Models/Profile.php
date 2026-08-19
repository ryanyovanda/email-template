<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string|null $headline
 * @property string $contact_email
 * @property string|null $phone
 * @property string|null $location
 * @property string|null $portfolio_url
 * @property string|null $linkedin_url
 * @property string|null $photo_url
 * @property string|null $photo_public_id
 * @property string|null $cv_url
 * @property string|null $cv_public_id
 * @property string|null $cv_filename
 * @property string|null $cv_resource_type
 * @property int|null $cv_bytes
 * @property string|null $cv_text
 * @property CarbonImmutable|null $cv_parsed_at
 * @property string|null $cv_parse_status
 */
#[Fillable([
    'full_name', 'headline', 'contact_email', 'phone', 'location', 'portfolio_url', 'linkedin_url',
    'photo_url', 'photo_public_id',
    'cv_url', 'cv_public_id', 'cv_filename', 'cv_resource_type', 'cv_bytes',
    'cv_text', 'cv_parsed_at', 'cv_parse_status',
])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The profile has the minimum needed to build an email.
     */
    public function isComplete(): bool
    {
        return filled($this->full_name) && filled($this->contact_email);
    }

    /**
     * There is CV text the AI can actually reason about.
     */
    public function hasUsableCvText(): bool
    {
        return mb_strlen(trim((string) $this->cv_text)) >= 200;
    }

    /**
     * A `wa.me` link when the phone looks like an Indonesian mobile number.
     */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if (blank($digits)) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }

    protected function casts(): array
    {
        return [
            'cv_parsed_at' => 'immutable_datetime',
            'cv_bytes' => 'integer',
        ];
    }
}
