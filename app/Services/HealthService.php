<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthService
{
    public function check(): array
    {
        $checks = [
            'database'          => $this->checkDatabase(),
            'database_external' => $this->checkDatabaseExternal(),
            'cache'             => $this->checkCache(),
        ];

        $allOk = !str_contains(implode('', array_values($checks)), 'error');

        return [
            'status'  => $allOk ? 'ok' : 'degraded',
            'app'     => config('app.name'),
            'env'     => config('app.env'),
            'version' => app()->version(),
            'checks'  => $checks,
            'time'    => now()->toIso8601String(),
        ];
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            return 'ok';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    private function checkDatabaseExternal(): string
    {
        try {
            DB::connection('external_db')->getPdo();
            return 'ok';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::put('health_check', true, 5);
            return Cache::get('health_check') ? 'ok' : 'error';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}
