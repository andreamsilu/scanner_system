<?php

namespace App\Providers;

use App\Services\NextSmsService;
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
            return new NextSmsService();
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