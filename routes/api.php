<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InsurerController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\InvoiceController;

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'store'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    // Rutas que requieren autenticación
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('/me', [AuthController::class, 'me'])->name('auth.me');
    });
});

// User Management Routes
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'users'
], function () {
    Route::post('/store', [UserController::class, 'storeUsers'])->name('users.storeMultiple');
    Route::patch('/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::post('/{id}/photoprofile', [UserController::class, 'photoProfile'])->name('users.photoProfile');
    Route::get('/', [UserController::class, 'index'])->name('users.index');
});

Route::apiResource('users', UserController::class)->middleware('role:dev|admin');


// Insurer Routes

Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'insurers'
], function () {
    Route::post('/store', [InsurerController::class, 'storeMultiple'])->name('insurers.storeMultiple');
    Route::apiResource('insurers', InsurerController::class);
});


// Medical Record Routes
Route::apiResource('medical-records', MedicalRecordController::class)->middleware('role:dev|admin');

// Admission Routes
Route::apiResource('admissions', AdmissionController::class)->middleware('role:dev|admin');

// Invoice Routes
Route::apiResource('invoices', InvoiceController::class)->middleware('role:dev|admin');