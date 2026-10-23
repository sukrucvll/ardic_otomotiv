<?php

use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use Illuminate\Support\Facades\Route;

/*
| Personel API (v1)
|
| Tüm uçlar servis token'ı ister: Authorization: Bearer <token>
|  - throttle:personnel-api    IP başına istek limiti (token deneme saldırılarına karşı)
|  - auth:sanctum              token doğrulama (hash karşılaştırma, süre kontrolü)
|  - abilities:employees:read  token'ın okuma yetkisi olmalı
*/
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['throttle:personnel-api', 'auth:sanctum', 'abilities:employees:read'])
    ->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('departments', DepartmentController::class)->name('departments.index');
    });
