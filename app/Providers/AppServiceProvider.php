<?php

namespace App\Providers;

use App\Services\ApiSisclinService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ApiSisclinService::class, function () {
            return new ApiSisclinService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        define('ADMISIONES', 'SC0011');
        define('SERVICIOS', 'SC0006');
        define('EMPRESAS', 'SC0003');
        define('PACIENTES', 'SC0004');
        define('DEVOLUCIONES', 'SC0033');
        define('FACTURAS', 'SC0017');
        define('ASEGURADORAS', 'SC0002');
        define('LIQUIDACIONES', 'SC0012');
    }
}
