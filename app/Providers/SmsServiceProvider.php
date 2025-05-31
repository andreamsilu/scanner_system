<?php

namespace App\Providers;

use App\Services\NextSmsService;
use App\Services\VonageSmsService;
use App\Contracts\SmsServiceInterface;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(SmsServiceInterface::class, function ($app) {
            $provider = config('services.sms.default_provider', 'nextsms');
            
            return match($provider) {
                'nextsms' => new NextSmsService(),
                'vonage' => new VonageSmsService(),
                default => throw new \InvalidArgumentException("Unsupported SMS provider: {$provider}"),
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