<?php

namespace App\Providers;

use App\Observers\IzinObserver;
use App\Observers\PersetujuanObserver;
use App\Models\Izin;
use App\Models\IzinPersetujuan;
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
        Izin::observe(IzinObserver::class);
        IzinPersetujuan::observe(PersetujuanObserver::class);
    }
}
