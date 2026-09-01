<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime-editable settings, layered over config defaults.
 *
 * A value set here (via the admin) wins; otherwise the matching key in
 * config/emailcv.php is used, so the app behaves exactly as before until an
 * admin changes something. The whole table is cached in one read and the cache
 * is dropped on any write, because settings change rarely but are read often.
 */
class Settings
{
    private const CACHE_KEY = 'settings.all';

    // Setting keys. Kept as constants so a typo is a code error, not a silent
    // miss that falls through to the default.
    public const PRICE_APPLICATION_DRAFT = 'price.application_draft';

    public const PRICE_TEMPLATE_DESIGN = 'price.template_design';

    public const MONTHLY_GRANT = 'credits.monthly_grant';

    public const PROMOTION_REWARD = 'credits.promotion_reward';

    /**
     * Every setting, keyed. Cached until a write clears it.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The credit price of a spend reason, admin-override first, then config.
     */
    public function priceOf(string $reason): int
    {
        $key = match ($reason) {
            'application_draft' => self::PRICE_APPLICATION_DRAFT,
            'template_design' => self::PRICE_TEMPLATE_DESIGN,
            default => null,
        };

        $configDefault = (int) config("emailcv.credits.prices.{$reason}", 0);

        if ($key === null) {
            return $configDefault;
        }

        return $this->getInt($key, $configDefault);
    }

    public function monthlyGrant(): int
    {
        return $this->getInt(
            self::MONTHLY_GRANT,
            (int) config('emailcv.credits.monthly_grant'),
        );
    }

    public function promotionReward(): int
    {
        return $this->getInt(
            self::PROMOTION_REWARD,
            (int) config('emailcv.credits.promotion_reward'),
        );
    }
}
