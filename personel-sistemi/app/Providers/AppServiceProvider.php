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

        // Türkçe arama/sıralama için SQLite'a tr_lower() ve "turkish" collation ekle.
        // PDO ilk sorguda açılır; fonksiyonlar da o anda eklenir (erken bağlantı açılmaz).
        Event::listen(function (ConnectionEstablished $event) {
            $connection = $event->connection;

            if ($connection instanceof SQLiteConnection) {
                $pdo = $connection->getRawPdo();

                $connection->setPdo(function () use ($pdo) {
                    $pdo = value($pdo);
                    TurkishText::registerSqliteFunctions($pdo);

                    return $pdo;
                });
            }
        });

        RateLimiter::for('personnel-api', function (Request $request) {
            return Limit::perMinute(config('personnel.api.rate_limit'))->by($request->ip());
        });
    }
}
