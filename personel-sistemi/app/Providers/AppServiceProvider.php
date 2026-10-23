<?php

namespace App\Providers;

use App\Support\TurkishText;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::useBootstrapFive();

        // Türkçe arama/sıralama için SQLite'a tr_lower() ve "turkish" collation ekle
        Event::listen(function (ConnectionEstablished $event) {
            if ($event->connection instanceof SQLiteConnection) {
                TurkishText::registerSqliteFunctions($event->connection->getPdo());
            }
        });

        RateLimiter::for('personnel-api', function (Request $request) {
            return Limit::perMinute(config('personnel.api.rate_limit'))->by($request->ip());
        });
    }
}
