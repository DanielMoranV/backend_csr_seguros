<?php

namespace App\Providers;

use App\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\AdmissionRepositoryInterface;
use App\Repositories\AdmissionRepository;
use App\Interfaces\InvoiceRepositoryInterface;
use App\Repositories\InvoiceRepository;
use App\Interfaces\InsurerRepositoryInterface;
use App\Repositories\InsurerRepository;
use App\Interfaces\MedicalRecordRepositoryInterface;
use App\Repositories\MedicalRecordRepository;
use App\Interfaces\DevolutionRepositoryInterface;
use App\Repositories\DevolutionRepository;
use App\Interfaces\AuditRepositoryInterface;
use App\Repositories\AuditRepository;
use App\Interfaces\SettlementRepositoryInterface;
use App\repositories\SettlementControllerRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AdmissionRepositoryInterface::class, AdmissionRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(InsurerRepositoryInterface::class, InsurerRepository::class);
        $this->app->bind(MedicalRecordRepositoryInterface::class, MedicalRecordRepository::class);
        $this->app->bind(DevolutionRepositoryInterface::class, DevolutionRepository::class);
        $this->app->bind(AuditRepositoryInterface::class, AuditRepository::class);
        $this->app->bind(SettlementRepositoryInterface::class, SettlementControllerRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}