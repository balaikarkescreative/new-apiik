<?php

namespace App\Providers;

use App\Observers\IzinObserver;
use App\Observers\PersetujuanObserver;
use App\Observers\VideoPanduanObserver;
use App\Models\Izin;
use App\Models\IzinPersetujuan;
use App\Models\VideoPanduan;
use Illuminate\Support\ServiceProvider;

use App\Models\User;

use Illuminate\Support\Facades\Gate;

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
        VideoPanduan::observe(VideoPanduanObserver::class);
        
        Gate::define('viewPulse', function (User $user) {

            return $user->hasRole('admin');

        });
    }
}
