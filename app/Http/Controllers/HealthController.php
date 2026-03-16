<?php

namespace App\Http\Controllers;

use App\Services\HealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(private HealthService $healthService) {}

    public function check(): JsonResponse
    {
        $result = $this->healthService->check();
        $status = $result['status'] === 'ok' ? 200 : 503;

        return response()->json($result, $status);
    }
}
