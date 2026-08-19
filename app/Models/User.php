<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property Carbon|null $banned_at
 * @property string|null $ban_reason
 * @property int|null $ai_monthly_limit
 * @property Carbon|null $onboarded_at
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $applications_count
 * @property-read int|null $ai_generations_today_count
 * @property-read int|null $ai_generations_this_month_count
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @return HasOne<Profile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return HasMany<AiGeneration, $this>
     */
    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarded_at !== null;
    }

    /**
     * Successful AI generations allowed per calendar month, per user override
     * falling back to the app-wide default.
     */
    public function monthlyAiLimit(): int
    {
        return $this->ai_monthly_limit ?? (int) config('emailcv.ai.monthly_limit');
    }

    public function dailyAiLimit(): int
    {
        return (int) config('emailcv.ai.daily_limit');
    }

    public function aiGenerationsThisMonth(): int
    {
        return $this->aiGenerations()
            ->where('status', 'success')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function aiGenerationsToday(): int
    {
        return $this->aiGenerations()
            ->where('status', 'success')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    public function remainingAiGenerations(): int
    {
        return max(0, min(
            $this->monthlyAiLimit() - $this->aiGenerationsThisMonth(),
            $this->dailyAiLimit() - $this->aiGenerationsToday(),
        ));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'banned_at' => 'datetime',
            'onboarded_at' => 'datetime',
        ];
    }
}
