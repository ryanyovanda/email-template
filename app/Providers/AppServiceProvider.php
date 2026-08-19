<?php

namespace App\Providers;

use App\Services\Uploads\CloudinaryUploader;
use App\Services\Uploads\LocalUploader;
use App\Services\Uploads\Uploader;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Uploader::class, function (): Uploader {
            if (! CloudinaryUploader::isConfigured()) {
                return new LocalUploader;
            }

            return new CloudinaryUploader(
                cloudName: (string) config('services.cloudinary.cloud_name'),
                apiKey: (string) config('services.cloudinary.api_key'),
                apiSecret: (string) config('services.cloudinary.api_secret'),
                baseFolder: (string) config('services.cloudinary.upload_folder'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
