<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the PDF facade
        $this->app->singleton('pdf', function ($app) {
            return new Pdf();
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