<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

// Yönetim ekranları yalnızca oturum açmış İK kullanıcılarına açık
Route::middleware('auth')->group(function () {
    Route::redirect('/', '/employees');

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::resource('employees', EmployeeController::class)->except('show');
});
