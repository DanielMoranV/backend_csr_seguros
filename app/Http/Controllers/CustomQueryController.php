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
        $this->middleware('compress')->only(['executeQuery', 'getAdmissionsByDateRange', 'getByMedicalRecordNumbers', 'getDevolutionsByInvoiceNumbers', 'getDevolutionsByDateRange']);
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
                ->leftJoin('sisclin.comprobantes as c', function ($join) {
                    $join->on('a.id', '=', 'c.atencion_id')
                        ->where(function ($q) {
                            $q->where('c.numero_factura', 'LIKE', '003-%')
                              ->orWhere('c.numero_factura', 'LIKE', '004-%');
                        });
                })
                ->leftJoin('sisclin.pagos_seguros as ps', 'c.numero_factura', '=', 'ps.numero_factura')
                ->leftJoin('sisclin.devoluciones as d', 'c.id', '=', 'd.comprobante_id')
                ->select(
                    'a.numero_documento AS number',
                    'a.fecha_hora_atencion AS attendance_date',
                    'p.nombre_paciente AS patient',
                    'a.fecha_hora_atencion AS attendance_hour',
                    'a.tipo_atencion AS type',
                    'a.total AS amount',
                    'e.nombre_empresa AS company',
                    'sm.nombre_servicio AS doctor',
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

    /**
     * Búsqueda masiva de devoluciones por números de factura.
     * Devuelve los mismos campos que la query legacy SC0033.
     * Campos sin equivalente en nueva BD (per_dev, tip_dev, usu_dev) se devuelven como null.
     */
    public function getDevolutionsByInvoiceNumbers(Request $request)
    {
        $invoiceNumbers = $request->input('invoice_numbers', []);

        if (empty($invoiceNumbers) || !is_array($invoiceNumbers)) {
            return ApiResponseClass::sendResponse([], 'Se requiere una lista de números de factura.', 400);
        }

        try {
            $results = DB::connection('external_db')
                ->table('sisclin.devoluciones as d')
                ->join('sisclin.comprobantes as c', 'd.comprobante_id', '=', 'c.id')
                ->join('sisclin.atenciones as a', 'c.atencion_id', '=', 'a.id')
                ->join('sisclin.pacientes as p', 'a.paciente_id', '=', 'p.id')
                ->join('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
                ->leftJoin('sisclin.servicios_medicos as sm', 'a.servicio_medico_id', '=', 'sm.id')
                ->leftJoin(
                    DB::connection('external_db')->raw('(
                        SELECT
                            c2.atencion_id,
                            MAX(c2.fecha_emision) AS date_last_invoice,
                            (ARRAY_AGG(c2.numero_factura ORDER BY c2.fecha_emision DESC))[1] AS last_invoice
                        FROM sisclin.comprobantes c2
                        WHERE c2.numero_factura LIKE \'003-%\' OR c2.numero_factura LIKE \'004-%\'
                        GROUP BY c2.atencion_id
                    ) AS last_invoice_data'),
                    'last_invoice_data.atencion_id', '=', 'a.id'
                )
                ->select(
                    'a.numero_documento AS number',
                    'd.id_dev AS id',
                    'd.fecha_devolucion AS date_dev',
                    'p.numero_historia AS medical_record_number',
                    'p.nombre_paciente AS patient',
                    'd.periodo AS period_dev',
                    'd.numero_factura AS invoice_number',
                    'c.fecha_emision AS invoice_date',
                    'c.total AS invoice_amount',
                    'as2.nombre_aseguradora AS insurer_name',
                    'a.fecha_hora_atencion AS attendance_date',
                    'sm.nombre_servicio AS doctor',
                    'd.estado_devolucion AS type',
                    'd.motivo AS reason',
                    'd.usuario_creacion AS biller',
                    'last_invoice_data.date_last_invoice',
                    'last_invoice_data.last_invoice',
                    DB::raw('EXISTS (
                        SELECT 1 FROM sisclin.pagos_seguros ps2
                        WHERE ps2.numero_factura = c.numero_factura
                    ) AS paid_admission')
                )
                ->whereIn('d.numero_factura', $invoiceNumbers)
                ->orderByDesc('a.numero_documento')
                ->get();

            return ApiResponseClass::sendResponse($results, 'Query executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error en getDevolutionsByInvoiceNumbers: ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'Error executing query: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Búsqueda de devoluciones por rango de fechas de atención.
     * Devuelve los mismos campos que la query legacy SC0033 por fecha.
     */
    public function getDevolutionsByDateRange(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        if (empty($startDate) || empty($endDate)) {
            return ApiResponseClass::sendResponse([], 'Se requiere start_date y end_date.', 400);
        }

        try {
            $results = DB::connection('external_db')
                ->table('sisclin.devoluciones as d')
                ->join('sisclin.comprobantes as c', 'd.comprobante_id', '=', 'c.id')
                ->join('sisclin.atenciones as a', 'c.atencion_id', '=', 'a.id')
                ->join('sisclin.pacientes as p', 'a.paciente_id', '=', 'p.id')
                ->join('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
                ->leftJoin('sisclin.servicios_medicos as sm', 'a.servicio_medico_id', '=', 'sm.id')
                ->leftJoin(
                    DB::connection('external_db')->raw('(
                        SELECT
                            c2.atencion_id,
                            MAX(c2.fecha_emision) AS date_last_invoice,
                            (ARRAY_AGG(c2.numero_factura ORDER BY c2.fecha_emision DESC))[1] AS last_invoice
                        FROM sisclin.comprobantes c2
                        WHERE c2.numero_factura LIKE \'003-%\' OR c2.numero_factura LIKE \'004-%\'
                        GROUP BY c2.atencion_id
                    ) AS last_invoice_data'),
                    'last_invoice_data.atencion_id', '=', 'a.id'
                )
                ->select(
                    'a.numero_documento AS number',
                    'd.id_dev AS id',
                    'd.fecha_devolucion AS date_dev',
                    'p.numero_historia AS medical_record_number',
                    'p.nombre_paciente AS patient',
                    'd.periodo AS period_dev',
                    'd.numero_factura AS invoice_number',
                    'c.fecha_emision AS invoice_date',
                    'c.total AS invoice_amount',
                    'as2.nombre_aseguradora AS insurer_name',
                    'a.fecha_hora_atencion AS attendance_date',
                    'p.nombre_paciente AS nom_pac',
                    'sm.nombre_servicio AS doctor',
                    'd.estado_devolucion AS type',
                    'd.motivo AS reason',
                    'd.usuario_creacion AS biller',
                    'last_invoice_data.date_last_invoice',
                    'last_invoice_data.last_invoice',
                    DB::raw('EXISTS (
                        SELECT 1 FROM sisclin.pagos_seguros ps2
                        WHERE ps2.numero_factura = c.numero_factura
                    ) AS paid_admission')
                )
                ->whereBetween('a.fecha_hora_atencion', [$startDate, $endDate])
                ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
                ->orderByDesc('d.id_dev')
                ->get();

            return ApiResponseClass::sendResponse($results, 'Query executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error en getDevolutionsByDateRange: ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'Error executing query: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Búsqueda masiva de pacientes por números de historia clínica.
     * El frontend envía una lista de números (con o sin ceros a la izquierda).
     * Devuelve: medical_record_number, patient, insurer_name
     */
    public function getByMedicalRecordNumbers(Request $request)
    {
        $numbers = $request->input('numbers', []);

        if (empty($numbers) || !is_array($numbers)) {
            return ApiResponseClass::sendResponse([], 'Se requiere una lista de números de historia.', 400);
        }

        // Normalizar a 9 dígitos con ceros a la izquierda
        $normalized = array_values(array_unique(
            array_map(fn($n) => str_pad((string) intval($n), 9, '0', STR_PAD_LEFT), $numbers)
        ));

        try {
            $results = DB::connection('external_db')
                ->table('sisclin.pacientes as p')
                ->leftJoin('sisclin.aseguradoras as as2', 'p.aseguradora_id', '=', 'as2.id')
                ->select(
                    'p.numero_historia AS medical_record_number',
                    'p.nombre_paciente AS patient',
                    'as2.nombre_aseguradora AS insurer_name'
                )
                ->whereIn('p.numero_historia', $normalized)
                ->orderBy('p.numero_historia')
                ->get();

            return ApiResponseClass::sendResponse($results, 'Query executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error en getByMedicalRecordNumbers: ' . $e->getMessage());
            return ApiResponseClass::sendResponse([], 'Error executing query: ' . $e->getMessage(), 500);
        }
    }
}
