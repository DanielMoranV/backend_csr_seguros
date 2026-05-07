<?php

namespace App\Services;

use App\Models\Devolution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DevolutionSyncService
{
    /**
     * Sincroniza devoluciones desde Sisclin hacia la tabla local.
     * Extrae por rango de fechas de atención.
     * is_paid se calcula a nivel de atención: si cualquier comprobante
     * de esa atención fue pagado, todas sus devoluciones se marcan pagadas.
     */
    public function syncByDateRange(string $startDate, string $endDate): array
    {
        $rows = $this->fetchFromSisclin($startDate, $endDate);
        return $this->upsertRows($rows);
    }

    /**
     * Sincroniza devoluciones desde Sisclin filtradas por números de factura.
     */
    public function syncByInvoiceNumbers(array $invoiceNumbers): array
    {
        $rows = $this->fetchByInvoiceNumbers($invoiceNumbers);
        return $this->upsertRows($rows);
    }

    private function fetchFromSisclin(string $startDate, string $endDate): \Illuminate\Support\Collection
    {
        return DB::connection('external_db')
            ->table('sisclin.devoluciones as d')
            ->join('sisclin.comprobantes as c', 'd.comprobante_id', '=', 'c.id')
            ->join('sisclin.atenciones as a', 'c.atencion_id', '=', 'a.id')
            ->join('sisclin.pacientes as p', 'a.paciente_id', '=', 'p.id')
            ->join('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->leftJoin('sisclin.servicios_medicos as sm', 'a.servicio_medico_id', '=', 'sm.id')
            ->select(
                'd.id_dev AS sisclin_id',
                'd.fecha_devolucion AS date',
                'd.estado_devolucion AS status',
                'd.motivo AS reason',
                'd.periodo AS period',
                'd.numero_factura AS invoice_number',
                'c.usuario_creacion AS biller',
                'c.fecha_emision AS invoice_date',
                'c.total AS invoice_amount',
                'a.numero_documento AS admission_number',
                'a.fecha_hora_atencion AS attendance_date',
                'p.nombre_paciente AS patient_name',
                'p.numero_historia AS medical_record_number',
                'as2.nombre_aseguradora AS insurer_name',
                'sm.nombre_servicio AS doctor',
                DB::raw('EXISTS (
                    SELECT 1 FROM sisclin.pagos_seguros ps
                    INNER JOIN sisclin.comprobantes c2 ON c2.numero_factura = ps.numero_factura
                    WHERE c2.atencion_id = a.id
                ) AS is_paid')
            )
            ->whereBetween('a.fecha_hora_atencion', [$startDate, $endDate])
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->get();
    }

    private function fetchByInvoiceNumbers(array $invoiceNumbers): \Illuminate\Support\Collection
    {
        return DB::connection('external_db')
            ->table('sisclin.devoluciones as d')
            ->join('sisclin.comprobantes as c', 'd.comprobante_id', '=', 'c.id')
            ->join('sisclin.atenciones as a', 'c.atencion_id', '=', 'a.id')
            ->join('sisclin.pacientes as p', 'a.paciente_id', '=', 'p.id')
            ->join('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->leftJoin('sisclin.servicios_medicos as sm', 'a.servicio_medico_id', '=', 'sm.id')
            ->select(
                'd.id_dev AS sisclin_id',
                'd.fecha_devolucion AS date',
                'd.estado_devolucion AS status',
                'd.motivo AS reason',
                'd.periodo AS period',
                'd.numero_factura AS invoice_number',
                'c.usuario_creacion AS biller',
                'c.fecha_emision AS invoice_date',
                'c.total AS invoice_amount',
                'a.numero_documento AS admission_number',
                'a.fecha_hora_atencion AS attendance_date',
                'p.nombre_paciente AS patient_name',
                'p.numero_historia AS medical_record_number',
                'as2.nombre_aseguradora AS insurer_name',
                'sm.nombre_servicio AS doctor',
                DB::raw('EXISTS (
                    SELECT 1 FROM sisclin.pagos_seguros ps
                    INNER JOIN sisclin.comprobantes c2 ON c2.numero_factura = ps.numero_factura
                    WHERE c2.atencion_id = a.id
                ) AS is_paid')
            )
            ->whereIn('d.numero_factura', $invoiceNumbers)
            ->get();
    }

    private function upsertRows(\Illuminate\Support\Collection $rows): array
    {
        $synced  = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $row = (array) $row;

            if (empty($row['sisclin_id'])) {
                $skipped++;
                continue;
            }

            try {
                // Resuelve invoice_id desde la tabla local si existe
                $invoiceId = null;
                if (!empty($row['invoice_number'])) {
                    $invoiceId = DB::table('invoices')
                        ->where('number', $row['invoice_number'])
                        ->value('id');
                }

                Devolution::updateOrCreate(
                    ['sisclin_id' => (string) $row['sisclin_id']],
                    [
                        'date'                  => $row['date'],
                        'status'                => $row['status'],
                        'reason'                => $row['reason'],
                        'period'                => $row['period'],
                        'biller'                => $row['biller'],
                        'invoice_date'          => $row['invoice_date'],
                        'invoice_amount'        => $row['invoice_amount'],
                        'admission_number'      => $row['admission_number'],
                        'attendance_date'       => $row['attendance_date'],
                        'medical_record_number' => $row['medical_record_number'],
                        'patient_name'          => $row['patient_name'],
                        'insurer_name'          => $row['insurer_name'],
                        'doctor'                => $row['doctor'],
                        'is_paid'               => (bool) $row['is_paid'],
                        'invoice_id'            => $invoiceId,
                        'type'                  => $row['status'],
                    ]
                );

                $synced++;
            } catch (\Exception $e) {
                Log::error("DevolutionSyncService: error en sisclin_id {$row['sisclin_id']}: " . $e->getMessage());
                $skipped++;
            }
        }

        return ['synced' => $synced, 'skipped' => $skipped];
    }
}
