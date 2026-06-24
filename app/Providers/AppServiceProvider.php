<?php

namespace App\Providers;

use App\Services\CompanyProfileService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        View::composer('layouts.portal', function ($view): void {
            $view->with('portalCompanyProfile', app(CompanyProfileService::class)->current());
        });
    }
}
