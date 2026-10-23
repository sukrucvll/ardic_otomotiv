<?php

namespace App\Providers;

use App\Services\Personnel\PersonnelApiClient;
use App\Support\TurkishText;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PersonnelApiClient::class, fn () => new PersonnelApiClient(
            baseUrl: rtrim((string) config('services.personnel.base_url'), '/'),
            token: config('services.personnel.token'),
            timeout: config('services.personnel.timeout'),
            cacheTtl: config('services.personnel.cache_ttl'),
        ));
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
    }
}
