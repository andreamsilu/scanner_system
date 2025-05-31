<?php

namespace App\Providers;

use App\Contracts\SmsServiceInterface;
use App\Services\VonageSmsService;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsServiceInterface::class, function ($app) {
            // Get the configured SMS provider from config
            $provider = config('services.sms.default_provider', 'vonage');
            
            // Return the appropriate service implementation
            return match ($provider) {
                'vonage' => new VonageSmsService(),
                // Add more providers here as needed
                default => throw new \RuntimeException("Unsupported SMS provider: {$provider}"),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 