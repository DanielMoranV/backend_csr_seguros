<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class ApiSisclinService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('FAST_API_BASE_URL', 'http://localhost:8000'); // URL de tu FastAPI
    }

    public function executeQuery(string $data): Response
    {
        try {

            return Http::timeout(30000)->post("{$this->baseUrl}/execute_query", ['query' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al conectar con FastAPI: ' . $e->getMessage()], 500);
        }
    }
}
