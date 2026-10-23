<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Eğitim Kategorileri
    |--------------------------------------------------------------------------
    |
    | Kategori adı => [Bootstrap renk, Bootstrap Icons simgesi]
    |
    */

    'categories' => [
        'İş Sağlığı ve Güvenliği' => ['danger', 'shield-check'],
        'Kalite' => ['success', 'patch-check'],
        'Teknik' => ['primary', 'gear'],
        'Yazılım ve Dijital' => ['info', 'laptop'],
        'Kişisel Gelişim' => ['warning', 'lightbulb'],
        'Uyum ve Mevzuat' => ['secondary', 'journal-text'],
    ],

    /*
    |--------------------------------------------------------------------------
    | İlk Yönetici Kullanıcısı
    |--------------------------------------------------------------------------
    |
    | `php artisan db:seed` ile oluşturulur. Canlı ortamda .env üzerinden
    | farklı bir parola verilmelidir.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Eğitim Sorumlusu'),
        'email' => env('ADMIN_EMAIL', 'egitim@sirket.test'),
        'password' => env('ADMIN_PASSWORD', 'Egitim.2026!'),
    ],

];
