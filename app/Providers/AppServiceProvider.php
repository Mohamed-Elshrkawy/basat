<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\CreateDailySchoolTrips;
use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\Events\NotificationFailed;
use App\Listeners\DeleteExpiredNotificationTokens;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;
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
        FilamentAsset::register([
            Css::make('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'),
            Js::make('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'),
        ]);

        $this->commands([
            CreateDailySchoolTrips::class,
        ]);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('app:create-daily-school-trips')->dailyAt('01:00');
        });

        // Register FCM notification failure event listener
        Event::listen(NotificationFailed::class, DeleteExpiredNotificationTokens::class);
    }
}
