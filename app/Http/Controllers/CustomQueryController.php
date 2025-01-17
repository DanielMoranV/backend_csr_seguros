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
        try {
            $results = DB::connection('external_db')->select($query);

            return ApiResponseClass::sendResponse($results, 'Query executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error executing query: ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'Error executing query: ' . $e->getMessage(), 500);
        }
    }

    public function getAdmissionsByDateRange(Request $request)
    {
        // Obtener las fechas del request
        $startDate = $request->input('start_date'); // Ejemplo: '2025-01-01'
        $endDate = $request->input('end_date');     // Ejemplo: '2025-01-14'

        // Validar que las fechas existan
        if (empty($startDate) || empty($endDate)) {
            return response()->json(['error' => 'Start date and end date are required'], 400);
        }

        $results = [];
        try {
            DB::connection('external_db')
                ->table('SC0011')
                ->leftJoin('SC0006', 'SC0011.cod_ser', '=', 'SC0006.cod_ser')
                ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
                ->leftJoin('SC0003', 'SC0011.cod_emp', '=', 'SC0003.cod_emp')
                ->leftJoin('SC0004', 'SC0011.cod_pac', '=', 'SC0004.cod_pac')
                ->leftJoin('SC0033', 'SC0011.num_doc', '=', 'SC0033.num_doc')
                ->leftJoin('SC0017', 'SC0011.num_doc', '=', 'SC0017.num_doc')
                ->leftJoin('SC0022', 'SC0017.num_doc', '=', 'SC0022.num_doc')
                ->select(
                    'SC0011.num_doc AS number',
                    'SC0011.fec_doc AS attendance_date',
                    'SC0011.nom_pac AS patient',
                    'SC0011.hi_doc AS attendance_hour',
                    'SC0011.ta_doc AS type',
                    'SC0011.tot_doc AS amount',
                    'SC0003.nom_emp AS company',
                    'SC0006.nom_ser AS doctor',
                    'SC0004.nh_pac AS medical_record_number',
                    'SC0011.clos_doc AS is_closed',
                    'SC0017.num_fac AS invoice_number',
                    'SC0017.fec_fac AS invoice_date',
                    'SC0017.uc_sis AS biller',
                    'SC0033.fh_dev AS devolution_date',
                    'SC0002.nom_cia AS insurer_name',
                    'SC0022.num_fac AS paid_invoice_number'
                )
                ->whereBetween('SC0011.fec_doc', [$startDate, $endDate]) // Rango de fechas
                ->where('SC0011.tot_doc', '>=', 0)
                ->where('SC0011.nom_pac', '<>', '')
                ->where('SC0011.nom_pac', '<>', 'No existe...')
                ->where('SC0002.nom_cia', '<>', 'PARTICULAR')
                ->where('SC0002.nom_cia', '<>', 'PACIENTES PARTICULARES')
                ->orderByDesc('SC0011.num_doc')
                ->chunk(500, function ($chunk) use (&$results) {
                    // Agrega el chunk procesado al arreglo de resultados
                    foreach ($chunk as $record) {
                        $results[] = $record;
                    }
                });

            return ApiResponseClass::sendResponse($results, 'Query executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error executing query: ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'Error executing query: ' . $e->getMessage(), 500);
        }
    }
}