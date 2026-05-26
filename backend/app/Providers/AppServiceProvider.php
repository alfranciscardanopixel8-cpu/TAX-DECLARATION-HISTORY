<?php

namespace App\Providers;

use App\Models\AssessmentRecord;
use App\Models\Document;
use App\Models\Property;
use App\Models\TaxDeclaration;
use App\Observers\DashboardCacheObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Invalidate dashboard cache whenever count-affecting models change.
        Property::observe(DashboardCacheObserver::class);
        TaxDeclaration::observe(DashboardCacheObserver::class);
        AssessmentRecord::observe(DashboardCacheObserver::class);
        Document::observe(DashboardCacheObserver::class);
    }
}
