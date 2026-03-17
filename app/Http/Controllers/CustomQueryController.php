<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomQueryController extends Controller
{
    public function __construct()
    {
        $this->middleware('compress')->only(['executeQuery', 'getAdmissionsByDateRange']);
    }

    public function executeQuery(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return ApiResponseClass::sendResponse([], 'Query is empty.', 400);
        }

        if (!preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE)\s+/i', $query)) {
            return ApiResponseClass::sendResponse([], 'Invalid query.', 400);
        }

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
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        if (empty($startDate) || empty($endDate)) {
            return response()->json(['error' => 'Start date and end date are required'], 400);
        }

        $results = [];
        try {
            DB::connection('external_db')
                ->table('sisclin.atenciones as a')
                ->leftJoin('sisclin.servicios_medicos as sm', 'a.servicio_medico_id', '=', 'sm.id')
                ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
                ->leftJoin('sisclin.empresas as e', 'a.empresa_id', '=', 'e.id')
                ->leftJoin('sisclin.pacientes as p', 'a.paciente_id', '=', 'p.id')
                ->leftJoin('sisclin.devoluciones as d', function ($join) {
                    $join->on('d.comprobante_id', '=', DB::raw(
                        '(SELECT id FROM sisclin.comprobantes WHERE atencion_id = a.id LIMIT 1)'
                    ));
                })
                ->leftJoin('sisclin.comprobantes as c', 'a.id', '=', 'c.atencion_id')
                ->leftJoin('sisclin.pagos_seguros as ps', 'c.numero_factura', '=', 'ps.numero_factura')
                ->select(
                    'a.numero_documento AS number',
                    'a.fecha_hora_atencion AS attendance_date',
                    'p.nombre_paciente AS patient',
                    'a.fecha_hora_atencion AS attendance_hour',
                    'a.tipo_atencion AS type',
                    'a.total AS amount',
                    'e.nombre_empresa AS company',
                    'sm.nombre_medico AS doctor',
                    'p.numero_historia AS medical_record_number',
                    'a.atencion_cerrada AS is_closed',
                    'c.numero_factura AS invoice_number',
                    'c.fecha_emision AS invoice_date',
                    'c.usuario_creacion AS biller',
                    'd.fecha_devolucion AS devolution_date',
                    'as2.nombre_aseguradora AS insurer_name',
                    'ps.numero_factura AS paid_invoice_number'
                )
                ->whereBetween('a.fecha_hora_atencion', [$startDate, $endDate])
                ->where('a.total', '>=', 0)
                ->where('p.nombre_paciente', '<>', '')
                ->where('p.nombre_paciente', '<>', 'No existe...')
                ->where('as2.nombre_aseguradora', '<>', 'PARTICULAR')
                ->where('as2.nombre_aseguradora', '<>', 'PACIENTES PARTICULARES')
                ->orderByDesc('a.numero_documento')
                ->chunk(500, function ($chunk) use (&$results) {
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
