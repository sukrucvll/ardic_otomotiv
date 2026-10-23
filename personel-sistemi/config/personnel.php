<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Departmanlar
    |--------------------------------------------------------------------------
    |
    | Personelin bağlı olabileceği departmanlar ve arayüzde kullanılan renkleri.
    | Renkler Bootstrap tema renkleridir (primary, success, info ...).
    |
    */

    'departments' => [
        'Üretim' => 'primary',
        'Kalite' => 'success',
        'Ar-Ge' => 'info',
        'Satış ve Pazarlama' => 'warning',
        'Satın Alma' => 'secondary',
        'Lojistik' => 'dark',
        'İnsan Kaynakları' => 'danger',
        'Bilgi Teknolojileri' => 'primary',
        'Finans' => 'success',
        'Satış Sonrası Hizmetler' => 'info',
    ],

    /*
    |--------------------------------------------------------------------------
    | İlk Yönetici (İK) Kullanıcısı
    |--------------------------------------------------------------------------
    |
    | `php artisan db:seed` ile oluşturulur. Canlı ortamda .env üzerinden
    | farklı bir parola verilmelidir.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'İK Yöneticisi'),
        'email' => env('ADMIN_EMAIL', 'ik@sirket.test'),
        'password' => env('ADMIN_PASSWORD', 'Ik.2026!'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'api' => [
        // Servis token'larına verilen yetkiler (Sanctum abilities)
        'abilities' => ['employees:read'],

        // IP başına dakikalık istek limiti
        'rate_limit' => (int) env('PERSONNEL_API_RATE_LIMIT', 60),

        'max_per_page' => 100,
    ],

];
