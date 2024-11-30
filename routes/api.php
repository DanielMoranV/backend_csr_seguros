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
    Route::patch('/update', [InsurerController::class, 'updateMultiple'])->name('insurers.updateMultiple');
});
Route::apiResource('insurers', InsurerController::class)->middleware('role:dev|admin');


// Medical Record Routes
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'medical-records'
], function () {
    Route::post('/store', [MedicalRecordController::class, 'storeMultiple'])->name('medical-records.storeMultiple');
    Route::patch('/update', [MedicalRecordController::class, 'updateMultiple'])->name('medical-records.updateMultiple');
});
Route::apiResource('medical-records', MedicalRecordController::class)->middleware('role:dev|admin');


// Admission Routes
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'admissions'
], function () {
    Route::post('/store', [AdmissionController::class, 'storeMultiple'])->name('admissions.storeMultiple');
    Route::patch('/update', [AdmissionController::class, 'updateMultiple'])->name('admissions.updateMultiple');
});
Route::apiResource('admissions', AdmissionController::class)->middleware('role:dev|admin');

// Invoice Routes
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'invoices'
], function () {
    Route::post('/store', [InvoiceController::class, 'storeMultiple'])->name('invoices.storeMultiple');
    Route::patch('/update', [InvoiceController::class, 'updateMultiple'])->name('invoices.updateMultiple');
});
Route::apiResource('invoices', InvoiceController::class)->middleware('role:dev|admin');