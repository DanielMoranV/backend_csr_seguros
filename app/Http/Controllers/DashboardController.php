<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Exports\DateRangeAnalysisExport;
use App\Exports\PeriodAnalysisExport;
use App\Http\Requests\DateRangeAnalysisRequest;
use App\Http\Requests\PeriodAnalysisRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

            // OPTIMIZACIÓN: Permitir diferentes modos de respuesta
            $includeAdmissions = $request->input('include_admissions', true);
            $aggregationsOnly = $request->input('aggregations_only', false);

            // Cachear por 10 minutos con clave única por configuración
            $cacheKey = "dashboard:date_range:{$startDate}:{$endDate}:"
                . ($aggregationsOnly ? 'agg' : ($includeAdmissions ? 'full' : 'meta'));

            $data = Cache::remember($cacheKey, 600, function () use ($startDate, $endDate, $includeAdmissions, $aggregationsOnly) {
                return $this->dashboardService->getDateRangeAnalysis(
                    $startDate,
                    $endDate,
                    $includeAdmissions,
                    $aggregationsOnly
                );
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

    /**
     * Exportar análisis por rango de fechas a Excel
     *
     * Genera y descarga un archivo Excel con todas las admisiones
     * del periodo especificado, incluyendo datos completos de facturación,
     * pagos, envíos y devoluciones.
     *
     * @param DateRangeAnalysisRequest $request
     * @return BinaryFileResponse
     */
    public function dateRangeAnalysisExport(DateRangeAnalysisRequest $request): BinaryFileResponse
    {
        try {
            $startDate = $request->validated('start_date');
            $endDate = $request->validated('end_date');

            // Obtener análisis con admisiones incluidas
            $data = $this->dashboardService->getDateRangeAnalysis(
                $startDate,
                $endDate,
                includeAdmissions: true,
                aggregationsOnly: false
            );

            $admissions = $data['admissions'] ?? [];

            // Generar nombre de archivo
            $filename = "analisis_rango_{$startDate}_{$endDate}.xlsx";

            // Crear y descargar Excel
            return Excel::download(
                new DateRangeAnalysisExport($admissions, $startDate, $endDate),
                $filename
            );

        } catch (\Exception $e) {
            Log::error('Error en dateRangeAnalysisExport: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Error al generar el archivo Excel: ' . $e->getMessage());
        }
    }

    /**
     * Exportar análisis por periodo a Excel
     *
     * Genera y descarga un archivo Excel con todas las admisiones
     * del periodo especificado (YYYYMM), incluyendo información detallada
     * de auditores, facturadores y estados de procesamiento.
     *
     * @param PeriodAnalysisRequest $request
     * @return BinaryFileResponse
     */
    public function periodAnalysisExport(PeriodAnalysisRequest $request): BinaryFileResponse
    {
        try {
            $period = $request->validated('period');

            // Obtener análisis completo (el servicio procesa y enriquece las admisiones)
            $data = $this->dashboardService->getPeriodAnalysisWithAdmissions($period);

            $admissions = $data['admissions'] ?? [];

            // Generar nombre de archivo
            $filename = "analisis_periodo_{$period}.xlsx";

            // Crear y descargar Excel
            return Excel::download(
                new PeriodAnalysisExport($admissions, $period),
                $filename
            );

        } catch (\Exception $e) {
            Log::error('Error en periodAnalysisExport: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Error al generar el archivo Excel: ' . $e->getMessage());
        }
    }
}
