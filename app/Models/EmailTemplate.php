<?php

namespace App\Models;

use App\Services\Templates\TemplateParser;
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
 */
#[Fillable([
    'name', 'slug', 'description', 'accent_color', 'thumbnail_url',
    'html', 'fields', 'is_active', 'sort_order', 'created_by',
])]
class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

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
        ];
    }
}
