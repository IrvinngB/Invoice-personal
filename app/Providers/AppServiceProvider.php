<?php

namespace App\Providers;

use App\Services\PdfService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['invoices.create', 'invoices.edit', 'invoices.show', 'settings.index'], function ($view) {
            $view->with('pdfTemplates', app(PdfService::class)->availableTemplates());
        });
    }
}
