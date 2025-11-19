<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\DateRangeAnalysisRequest;
use App\Http\Requests\PeriodAnalysisRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
        $this->middleware('compress')->only(['dateRangeAnalysis', 'periodAnalysis']);
    }

    /**
     * Análisis por rango de fechas
     *
     * Endpoint principal para obtener estadísticas y reportes de admisiones
     * en un rango de fechas específico. Procesa datos de ambas bases de datos:
     * - MySQL Legado (external_db): Tablas SC00XX
     * - SQLite Aplicación: Tablas shipments, audits, etc.
     *
     * @param DateRangeAnalysisRequest $request
     * @return JsonResponse
     */
    public function dateRangeAnalysis(DateRangeAnalysisRequest $request): JsonResponse
    {
        try {
            $startDate = $request->validated('start_date');
            $endDate = $request->validated('end_date');

            // Cachear por 10 minutos
            $cacheKey = "dashboard:date_range:{$startDate}:{$endDate}";

            $data = Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
                return $this->dashboardService->getDateRangeAnalysis($startDate, $endDate);
            });

            return ApiResponseClass::sendResponse($data, 'Análisis por rango de fechas obtenido exitosamente');

        } catch (\Exception $e) {
            Log::error('Error en dateRangeAnalysis: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponseClass::sendResponse(
                [],
                'Error al procesar la solicitud: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Análisis por periodo
     *
     * Endpoint para obtener estadísticas y reportes de admisiones por periodo (YYYYMM).
     * Incluye análisis de rendimiento de auditores y facturadores.
     * Procesa datos de ambas bases de datos.
     *
     * @param PeriodAnalysisRequest $request
     * @return JsonResponse
     */
    public function periodAnalysis(PeriodAnalysisRequest $request): JsonResponse
    {
        try {
            $period = $request->validated('period');

            $cacheKey = "dashboard:period:{$period}";

            $data = Cache::remember($cacheKey, 600, function () use ($period) {
                return $this->dashboardService->getPeriodAnalysis($period);
            });

            return ApiResponseClass::sendResponse($data, 'Análisis por periodo obtenido exitosamente');

        } catch (\Exception $e) {
            Log::error('Error en periodAnalysis: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponseClass::sendResponse(
                [],
                'Error al procesar la solicitud: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Limpiar caché del dashboard
     *
     * Endpoint auxiliar para limpiar el caché de reportes.
     * Útil cuando se actualizan datos y se necesita refrescar las estadísticas.
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            Cache::flush();

            return ApiResponseClass::sendResponse(
                ['cache_cleared' => true],
                'Caché del dashboard limpiado exitosamente'
            );

        } catch (\Exception $e) {
            Log::error('Error al limpiar caché: ' . $e->getMessage());

            return ApiResponseClass::sendResponse(
                [],
                'Error al limpiar caché: ' . $e->getMessage(),
                500
            );
        }
    }
}
