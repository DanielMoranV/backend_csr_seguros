<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomQueryController extends Controller
{
    public function executeQuery(Request $request)
    {
        // validar si query existe y es valido
        $query = $request->input('query');

        if (empty($query)) {
            return ApiResponseClass::sendResponse([], 'Query is empty.', 400);
        }

        if (!preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE)\s+/i', $query)) {
            return ApiResponseClass::sendResponse([], 'Invalid query.', 400);
        }
        $query = $request->input('query');

        Log::debug($query);

        try {
            $results = DB::connection('external_db')->select($query);
            Log::debug($results);
            return ApiResponseClass::sendResponse($results, 'Query executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error executing query: ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'Error executing query: ' . $e->getMessage(), 500);
        }
    }
}