<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MigrationController extends Controller
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('MIGRATION_API_URL', 'http://localhost:8081/api/v1'), '/');
        $this->apiKey  = env('MIGRATION_API_KEY', '');
    }

    /**
     * Lanza migración de atenciones por rango de fechas.
     * POST /api/migration/atenciones
     *
     * Body: { start_date, end_date, include_dependencies?, dry_run? }
     */
    public function migrateAtenciones(Request $request)
    {
        $request->validate([
            'start_date'           => 'required|date_format:Y-m-d',
            'end_date'             => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'include_dependencies' => 'boolean',
            'dry_run'              => 'boolean',
        ]);

        $payload = [
            'start_date'           => $request->input('start_date'),
            'end_date'             => $request->input('end_date'),
            'include_dependencies' => $request->input('include_dependencies', true),
            'dry_run'              => $request->input('dry_run', false),
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['x-api-key' => $this->apiKey])
                ->post("{$this->baseUrl}/migration/by-date/atenciones", $payload);

            if ($response->failed()) {
                Log::error('Migration API error: ' . $response->body());
                return ApiResponseClass::sendResponse(
                    $response->json() ?? [],
                    'Error al iniciar migración: ' . $response->body(),
                    $response->status()
                );
            }

            return ApiResponseClass::sendResponse($response->json(), 'Migración iniciada correctamente.', 200);
        } catch (\Exception $e) {
            Log::error('Error conectando con Migration API: ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'No se pudo conectar con el servicio de migración.', 500);
        }
    }

    /**
     * Lanza migración de devoluciones por rango de fechas.
     * POST /api/migration/by-date/devoluciones
     *
     * Body: { start_date, end_date, include_dependencies?, dry_run? }
     */
    public function migrateDevoluciones(Request $request)
    {
        $request->validate([
            'start_date'           => 'required|date_format:Y-m-d',
            'end_date'             => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'include_dependencies' => 'boolean',
            'dry_run'              => 'boolean',
        ]);

        $payload = [
            'start_date'           => $request->input('start_date'),
            'end_date'             => $request->input('end_date'),
            'include_dependencies' => $request->input('include_dependencies', true),
            'dry_run'              => $request->input('dry_run', false),
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['x-api-key' => $this->apiKey])
                ->post("{$this->baseUrl}/migration/by-date/devoluciones", $payload);

            if ($response->failed()) {
                Log::error('Migration API error (devoluciones): ' . $response->body());
                return ApiResponseClass::sendResponse(
                    $response->json() ?? [],
                    'Error al iniciar migración de devoluciones: ' . $response->body(),
                    $response->status()
                );
            }

            return ApiResponseClass::sendResponse($response->json(), 'Migración de devoluciones iniciada correctamente.', 200);
        } catch (\Exception $e) {
            Log::error('Error conectando con Migration API (devoluciones): ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'No se pudo conectar con el servicio de migración.', 500);
        }
    }

    /**
     * Lanza migración selectiva de admisiones por números.
     * POST /api/v1/custom-migrations/admissions
     *
     * Body: { admission_numbers, dry_run? }
     */
    public function migrateAdmissions(Request $request)
    {
        $request->validate([
            'admission_numbers' => 'required|array|min:1',
            'admission_numbers.*' => 'string',
            'dry_run' => 'boolean',
        ]);

        $payload = [
            'admission_numbers' => $request->input('admission_numbers'),
            'dry_run' => $request->input('dry_run', false),
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders(['x-api-key' => $this->apiKey])
                ->post("{$this->baseUrl}/custom-migrations/admissions", $payload);

            if ($response->failed()) {
                Log::error('Selective Migration API error: ' . $response->body());
                return ApiResponseClass::sendResponse(
                    $response->json() ?? [],
                    'Error al iniciar migración selectiva: ' . $response->body(),
                    $response->status()
                );
            }

            return ApiResponseClass::sendResponse($response->json(), 'Migración selectiva iniciada correctamente.', 200);
        } catch (\Exception $e) {
            Log::error('Error conectando con Migration API (Selective): ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'No se pudo conectar con el servicio de migración.', 500);
        }
    }

    /**
     * Consulta el estado de un job de migración.
     * GET /api/migration/status/{jobId}
     */
    public function getStatus(string $jobId)
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['x-api-key' => $this->apiKey])
                ->get("{$this->baseUrl}/migration/status/{$jobId}");

            if ($response->failed()) {
                Log::error("Migration status error [{$jobId}]: " . $response->body());
                return ApiResponseClass::sendResponse(
                    $response->json() ?? [],
                    'Error consultando estado: ' . $response->body(),
                    $response->status()
                );
            }

            return ApiResponseClass::sendResponse($response->json(), '', 200);
        } catch (\Exception $e) {
            Log::error('Error conectando con Migration API: ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'No se pudo conectar con el servicio de migración.', 500);
        }
    }
}
