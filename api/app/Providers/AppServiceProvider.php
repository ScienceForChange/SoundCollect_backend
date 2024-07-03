<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Http;


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
        Relation::enforceMorphMap([
            'user'  => 'App\Models\User',
            'citizen' => 'App\Models\ProfileCitizen',
            'client' => 'App\Models\ProfileClient',
        ]);

        Http::macro('openWeather', function () {
            return Http::withQueryParameters([
                'appid' => config('services.openweathermap.key'),
                'units' => config('services.openweathermap.units'),
            ])->baseUrl(config('services.openweathermap.url'));
        });
    }
}
