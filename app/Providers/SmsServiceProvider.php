<?php

namespace App\Providers;

use App\Contracts\SmsServiceInterface;
use App\Services\NextSmsService;
use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the SMS provider implementation
        $this->app->singleton(SmsServiceInterface::class, function ($app) {
            return new NextSmsService();
        });

        // Register the main SMS service
        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService($app->make(SmsServiceInterface::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add SMS logging channel if not exists
        if (!isset($this->app['config']['logging.channels.sms'])) {
            $this->app['config']->set('logging.channels.sms', [
                'driver' => 'daily',
                'path' => storage_path('logs/sms.log'),
                'level' => env('SMS_LOG_LEVEL', 'debug'),
                'days' => 14,
            ]);
        }
    }
} 