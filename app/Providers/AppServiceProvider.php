<?php

namespace App\Providers;

use App\Models\Izin;
use App\Models\IzinPersetujuan;
use App\Models\User;
use App\Models\VideoPanduan;
use App\Observers\IzinObserver;
use App\Observers\PersetujuanObserver;
use App\Observers\VideoPanduanObserver;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Assets\Js;

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

        FilamentAsset::register([
            Js::make('sienna','https://website-widgets.pages.dev/dist/sienna.min.js')->defer(),
        ]);
    }
}
