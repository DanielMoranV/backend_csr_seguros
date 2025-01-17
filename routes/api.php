<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InsurerController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\DevolutionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\AdmissionsListController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\CustomQueryController;
use App\Http\Controllers\MedicalRecordRequestController;

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
    Route::post('/date-range', [AdmissionController::class, 'admissionsByDateRange'])->name('admissions.admissionsByDateRange');
    Route::get('/by-number/{number}', [AdmissionController::class, 'admissionByNumber'])->name('admissions.admissionByNumber');
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

// Devolution Routes
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'devolutions'
], function () {
    Route::post('/store', [DevolutionController::class, 'storeMultiple'])->name('devolutions.storeMultiple');
    Route::patch('/update', [DevolutionController::class, 'updateMultiple'])->name('devolutions.updateMultiple');
});
Route::apiResource('devolutions', DevolutionController::class)->middleware('role:dev|admin');

// Settlement Routes
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'settlements'
], function () {
    Route::post('/store', [SettlementController::class, 'storeMultiple'])->name('settlements.storeMultiple');
    Route::patch('/update', [SettlementController::class, 'updateMultiple'])->name('settlements.updateMultiple');
});
Route::apiResource('settlements', SettlementController::class)->middleware('role:dev|admin');

// Admissions List Routes
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'admissions-lists'
], function () {
    Route::post('/store', [AdmissionsListController::class, 'storeMultiple'])->name('admissions-lists.storeMultiple');
    Route::patch('/update', [AdmissionsListController::class, 'updateMultiple'])->name('admissions-lists.updateMultiple');
    Route::post('/create-admission-list-and-request', [AdmissionsListController::class, 'createAdmissionsLists'])->name('admissions-lists.createAdmissionsLists');
    // ruta para obtener todos los periodos disponibles
    Route::get('/periods', [AdmissionsListController::class, 'getAllPeriods'])->name('admissions-lists.getAllPeriods');
    // ruta get para obtener por periodo
    Route::get('/by-period/{period}', [AdmissionsListController::class, 'getByPeriod'])->name('admissions-lists.getByPeriod');
});
Route::apiResource('admissions-lists', AdmissionsListController::class)->middleware('role:dev|admin');

// Audit Routes
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'audits'
], function () {
    Route::post('/store', [AuditController::class, 'storeMultiple'])->name('audits.storeMultiple');
    Route::patch('/update', [AuditController::class, 'updateMultiple'])->name('audits.updateMultiple');
    Route::post('/by-admissions', [AuditController::class, 'getAuditsByAdmissions'])->name('audits.getAuditsByAdmissions');
    Route::post('/by-date-range', [AuditController::class, 'getAuditsByDateRange'])->name('audits.getAuditsByDateRange');
});
Route::apiResource('audits', AuditController::class)->middleware('role:dev|admin');


// Medical Records Requests
Route::group([
    'middleware' => ['auth:api', 'role:dev|admin'],
    'prefix' => 'medical-records-requests'
], function () {
    Route::post('/store', [MedicalRecordRequestController::class, 'storeMultiple'])->name('medical-records-requests.storeMultiple');
    Route::patch('/update', [MedicalRecordRequestController::class, 'updateMultiple'])->name('medical-records-requests.updateMultiple');
});
Route::apiResource('medical-records-requests', MedicalRecordRequestController::class)->middleware('role:dev|admin');



Route::post('excequte_query', [CustomQueryController::class, 'executeQuery'])->name('executeQuery');
Route::post('admissions_by_date_range', [CustomQueryController::class, 'getAdmissionsByDateRange'])->name('getAdmissionsByDateRange');