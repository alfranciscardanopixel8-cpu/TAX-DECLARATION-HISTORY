<?php

namespace App\Observers;

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Database\Eloquent\Model;

/**
 * Single observer used for every model whose changes affect dashboard counts.
 * Keeps cache invalidation in one place instead of scattered Cache::forget() calls.
 */
class DashboardCacheObserver
{
    public function saved(Model $model): void
    {
        DashboardController::bust();
    }

    public function deleted(Model $model): void
    {
        DashboardController::bust();
    }
}
