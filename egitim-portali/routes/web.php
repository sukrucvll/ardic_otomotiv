<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TrainingAssignmentController;
use App\Http\Controllers\TrainingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

// Portal ekranları yalnızca oturum açmış kullanıcılara açık
Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::resource('trainings', TrainingController::class);

    Route::get('assignments', [TrainingAssignmentController::class, 'index'])->name('assignments.index');
    Route::post('trainings/{training}/assignments', [TrainingAssignmentController::class, 'store'])->name('assignments.store');
    Route::patch('assignments/{assignment}', [TrainingAssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('assignments/{assignment}', [TrainingAssignmentController::class, 'destroy'])->name('assignments.destroy');

    // {employee} Personel Sistemi'ndeki ID'dir; PHP tam sayı sınırını aşan değerler 404 olur
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->where('employee', '[0-9]{1,18}')->name('employees.show');
    Route::post('employees/{employee}/assignments', [TrainingAssignmentController::class, 'storeForEmployee'])->where('employee', '[0-9]{1,18}')->name('employees.assignments.store');
});
