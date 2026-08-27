<?php

namespace App\Models;

use App\Services\Templates\TemplateParser;
use Carbon\CarbonImmutable;
use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $created_by
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $accent_color
 * @property string|null $thumbnail_url
 * @property string $html
 * @property array<int, array<string, mixed>>|null $fields
 * @property bool $is_active
 * @property int $sort_order
 * @property string $visibility
 * @property string $origin
 * @property string|null $brief
 * @property CarbonImmutable|null $terms_accepted_at
 * @property CarbonImmutable|null $promoted_at
 * @property int|null $promoted_by
 */
#[Fillable([
    'name', 'slug', 'description', 'accent_color', 'thumbnail_url',
    'html', 'fields', 'is_active', 'sort_order', 'created_by',
    'visibility', 'origin', 'brief', 'terms_accepted_at',
])]
class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

    /** Shipped with the platform, or hand-written by an administrator. */
    public const ORIGIN_SYSTEM = 'system';

    /** Designed by the AI from a user's brief. */
    public const ORIGIN_GENERATED = 'user';

    /** HTML a user wrote or pasted in themselves. */
    public const ORIGIN_HAND_WRITTEN = 'user_html';

    /**
     * The origins that belong to a user rather than the platform, and so are
     * subject to review before they can be shared.
     *
     * @var array<int, string>
     */
    public const USER_ORIGINS = [self::ORIGIN_GENERATED, self::ORIGIN_HAND_WRITTEN];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Templates offered to everyone on the platform.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeGlobal(Builder $query): void
    {
        $query->where('visibility', 'global');
    }

    /**
     * What a given user may choose from: the shared library plus anything they
     * had designed for themselves.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(fn (Builder $inner) => $inner
            ->where('visibility', 'global')
            ->orWhere('created_by', $user->id));
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeMadeByUsers(Builder $query): void
    {
        $query->whereIn('origin', self::USER_ORIGINS);
    }

    public function isGlobal(): bool
    {
        return $this->visibility === 'global';
    }

    public function isUserMade(): bool
    {
        return in_array($this->origin, self::USER_ORIGINS, true);
    }

    /**
     * Written by hand rather than generated. Worth distinguishing when an
     * administrator reviews a design for the shared library: pasted markup
     * carries a copyright question that generated markup does not.
     */
    public function isHandWritten(): bool
    {
        return $this->origin === self::ORIGIN_HAND_WRITTEN;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }

    /**
     * Field definitions the user actually fills in — profile-backed tokens are
     * resolved automatically and never shown as form inputs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function userFields(): array
    {
        return array_values(array_filter(
            $this->fields ?? [],
            fn (array $field): bool => ! TemplateParser::isSystemToken($field['token'] ?? '')
        ));
    }

    /**
     * Starting values for a brand new draft, so a template can ship sensible
     * copy for fields a user would otherwise have to invent.
     *
     * @return array<string, string>
     */
    public function defaultValues(): array
    {
        $values = [];

        foreach ($this->userFields() as $field) {
            if (filled($field['default'] ?? null)) {
                $values[(string) $field['token']] = (string) $field['default'];
            }
        }

        return $values;
    }

    /**
     * The tokens the AI is expected to write copy for.
     *
     * @return array<int, array<string, mixed>>
     */
    public function aiFields(): array
    {
        return array_values(array_filter(
            $this->userFields(),
            fn (array $field): bool => (bool) ($field['ai'] ?? false)
        ));
    }

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'terms_accepted_at' => 'immutable_datetime',
            'promoted_at' => 'immutable_datetime',
        ];
    }
}
