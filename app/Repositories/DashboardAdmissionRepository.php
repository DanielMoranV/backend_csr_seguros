<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DashboardAdmissionRepository
{
    /**
     * Obtener admisiones deduplicadas con factura más reciente.
     * Solo se considera factura válida la serie 003 o 004 (política clínica).
     */
    public function getUniqueAdmissionsByDateRange(string $startDate, string $endDate): array
    {
        $startDateQuoted = DB::connection('external_db')->getPdo()->quote($startDate);
        $endDateQuoted   = DB::connection('external_db')->getPdo()->quote($endDate);

        $sql = "
            SELECT
                a.numero_documento                        AS number,
                a.fecha_hora_atencion                     AS attendance_date,
                p.nombre_paciente                         AS patient,
                a.fecha_hora_atencion                     AS attendance_hour,
                a.tipo_atencion                           AS type,
                a.total                                   AS amount,
                a.codigo_paciente                         AS patient_code,
                a.atencion_cerrada                        AS is_closed,
                e.nombre_empresa                          AS company,
                sm.nombre_medico                          AS doctor,
                p.numero_historia                         AS medical_record_number,
                c.numero_factura                          AS invoice_number,
                c.fecha_emision                           AS invoice_date,
                c.usuario_creacion                        AS biller,
                d.fecha_devolucion                        AS devolution_date,
                d.numero_factura                          AS devolution_invoice_number,
                as2.nombre_aseguradora                    AS insurer_name,
                ps.numero_factura                         AS paid_invoice_number,
                EXTRACT(MONTH FROM a.fecha_hora_atencion) AS month,
                (CURRENT_DATE - a.fecha_hora_atencion::date) AS days_passed,
                CASE
                    WHEN c.numero_factura IS NULL
                        OR (c.numero_factura NOT LIKE '003-%' AND c.numero_factura NOT LIKE '004-%')
                    THEN 'Pendiente'

                    WHEN d.fecha_devolucion IS NOT NULL
                        AND ps.numero_factura IS NULL
                    THEN 'Devolución'

                    WHEN ps.numero_factura IS NOT NULL
                    THEN 'Pagado'

                    ELSE 'Liquidado'
                END AS status
            FROM sisclin.atenciones a
            LEFT JOIN sisclin.aseguradoras as2 ON a.aseguradora_id = as2.id
            LEFT JOIN sisclin.empresas e ON a.empresa_id = e.id
            LEFT JOIN sisclin.pacientes p ON a.paciente_id = p.id
            LEFT JOIN sisclin.servicios_medicos sm ON a.servicio_medico_id = sm.id
            LEFT JOIN sisclin.devoluciones d ON d.comprobante_id = (
                SELECT id FROM sisclin.comprobantes WHERE atencion_id = a.id LIMIT 1
            )
            LEFT JOIN (
                SELECT
                    atencion_id,
                    MAX(CASE
                        WHEN numero_factura LIKE '003-%' OR numero_factura LIKE '004-%'
                        THEN fecha_emision::text || '|' || numero_factura
                        ELSE NULL
                    END) AS max_invoice_key
                FROM sisclin.comprobantes
                GROUP BY atencion_id
            ) latest ON a.id = latest.atencion_id
            LEFT JOIN sisclin.comprobantes c ON a.id = c.atencion_id
                AND latest.max_invoice_key IS NOT NULL
                AND (c.fecha_emision::text || '|' || c.numero_factura) = latest.max_invoice_key
            LEFT JOIN sisclin.pagos_seguros ps ON c.numero_factura = ps.numero_factura
            WHERE a.fecha_hora_atencion BETWEEN {$startDateQuoted} AND {$endDateQuoted}
                AND a.total >= 0
                AND p.nombre_paciente != ''
                AND p.nombre_paciente != 'No existe...'
                AND as2.nombre_aseguradora NOT IN ('PARTICULAR', 'PACIENTES PARTICULARES')
        ";

        $results = DB::connection('external_db')->select($sql);

        return array_map(fn($item) => (array) $item, $results);
    }

    /**
     * Obtener admisiones SOLO para agregaciones (sin enriquecimiento)
     */
    public function getAdmissionsForAggregation(string $startDate, string $endDate): array
    {
        $startDateQuoted = DB::connection('external_db')->getPdo()->quote($startDate);
        $endDateQuoted   = DB::connection('external_db')->getPdo()->quote($endDate);

        $sql = "
            SELECT
                a.numero_documento                        AS number,
                EXTRACT(MONTH FROM a.fecha_hora_atencion) AS month,
                a.tipo_atencion                           AS type,
                a.total                                   AS amount,
                a.codigo_paciente                         AS patient_code,
                e.nombre_empresa                          AS company,
                as2.nombre_aseguradora                    AS insurer_name,
                c.numero_factura                          AS invoice_number,
                ps.numero_factura                         AS paid_invoice_number,
                CASE
                    WHEN c.numero_factura IS NULL
                        OR (c.numero_factura NOT LIKE '003-%' AND c.numero_factura NOT LIKE '004-%')
                    THEN 'Pendiente'
                    WHEN ps.numero_factura IS NOT NULL
                    THEN 'Pagado'
                    ELSE 'Liquidado'
                END AS status
            FROM sisclin.atenciones a
            LEFT JOIN sisclin.empresas e ON a.empresa_id = e.id
            LEFT JOIN sisclin.aseguradoras as2 ON a.aseguradora_id = as2.id
            LEFT JOIN sisclin.comprobantes c ON a.id = c.atencion_id
                AND (c.numero_factura LIKE '003-%' OR c.numero_factura LIKE '004-%')
            LEFT JOIN sisclin.pagos_seguros ps ON c.numero_factura = ps.numero_factura
            WHERE a.fecha_hora_atencion BETWEEN {$startDateQuoted} AND {$endDateQuoted}
                AND a.total >= 0
                AND (SELECT nombre_paciente FROM sisclin.pacientes WHERE id = a.paciente_id LIMIT 1) != ''
                AND (SELECT nombre_paciente FROM sisclin.pacientes WHERE id = a.paciente_id LIMIT 1) != 'No existe...'
                AND as2.nombre_aseguradora NOT IN ('PARTICULAR', 'PACIENTES PARTICULARES')
        ";

        $results = DB::connection('external_db')->select($sql);

        return array_map(fn($item) => (array) $item, $results);
    }

    /**
     * Obtener admisiones por números específicos
     */
    public function getAdmissionsByNumbers(array $numbers): array
    {
        if (empty($numbers)) {
            return [];
        }

        $baseQuery = $this->buildBaseQuery()
            ->whereIn('a.numero_documento', $numbers)
            ->orderByDesc('a.numero_documento');

        return $baseQuery->get()->map(fn($item) => (array) $item)->all();
    }

    /**
     * Query base con todos los joins necesarios.
     * Factura válida = serie 003 o 004.
     */
    protected function buildBaseQuery()
    {
        return DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->leftJoin('sisclin.empresas as e', 'a.empresa_id', '=', 'e.id')
            ->leftJoin('sisclin.pacientes as p', 'a.paciente_id', '=', 'p.id')
            ->leftJoin('sisclin.servicios_medicos as sm', 'a.servicio_medico_id', '=', 'sm.id')
            ->leftJoin('sisclin.comprobantes as c', function ($join) {
                $join->on('a.id', '=', 'c.atencion_id')
                    ->where(function ($q) {
                        $q->where('c.numero_factura', 'LIKE', '003-%')
                          ->orWhere('c.numero_factura', 'LIKE', '004-%');
                    });
            })
            ->leftJoin('sisclin.pagos_seguros as ps', 'c.numero_factura', '=', 'ps.numero_factura')
            ->leftJoin('sisclin.devoluciones as d', 'c.id', '=', 'd.comprobante_id')
            ->select([
                'a.numero_documento as number',
                'a.fecha_hora_atencion as attendance_date',
                'p.nombre_paciente as patient',
                'a.fecha_hora_atencion as attendance_hour',
                'a.tipo_atencion as type',
                'a.total as amount',
                'a.codigo_paciente as patient_code',
                'a.atencion_cerrada as is_closed',
                'e.nombre_empresa as company',
                'sm.nombre_medico as doctor',
                'p.numero_historia as medical_record_number',
                'c.numero_factura as invoice_number',
                'c.fecha_emision as invoice_date',
                'c.usuario_creacion as biller',
                'd.fecha_devolucion as devolution_date',
                'd.numero_factura as devolution_invoice_number',
                'as2.nombre_aseguradora as insurer_name',
                'ps.numero_factura as paid_invoice_number',
                DB::raw('EXTRACT(MONTH FROM a.fecha_hora_atencion) as month'),
                DB::raw('(CURRENT_DATE - a.fecha_hora_atencion::date) as days_passed'),
                DB::raw("
                    CASE
                        WHEN c.numero_factura IS NULL
                            OR (c.numero_factura NOT LIKE '003-%' AND c.numero_factura NOT LIKE '004-%')
                        THEN 'Pendiente'

                        WHEN d.fecha_devolucion IS NOT NULL
                            AND ps.numero_factura IS NULL
                        THEN 'Devolución'

                        WHEN ps.numero_factura IS NOT NULL
                        THEN 'Pagado'

                        ELSE 'Liquidado'
                    END as status
                ")
            ]);
    }

    /**
     * Enriquecer admisiones con datos de envíos
     */
    public function enrichWithShipments(array $admissions): array
    {
        if (empty($admissions)) {
            return $admissions;
        }

        $invoiceNumbers = array_values(array_unique(array_filter(array_column($admissions, 'invoice_number'))));

        if (empty($invoiceNumbers)) {
            return $admissions;
        }

        $shipments = DB::table('shipments')
            ->select('invoice_number', 'verified_shipment_date')
            ->whereIn('invoice_number', $invoiceNumbers)
            ->whereNotNull('verified_shipment_date')
            ->get()
            ->keyBy('invoice_number');

        $shipmentsArray = [];
        foreach ($shipments as $key => $shipment) {
            $shipmentsArray[$key] = $shipment->verified_shipment_date;
        }

        foreach ($admissions as &$admission) {
            if (isset($admission['invoice_number']) && isset($shipmentsArray[$admission['invoice_number']])) {
                if ($admission['status'] === 'Liquidado') {
                    $admission['status'] = 'Enviado';
                }
                $admission['verified_shipment_date'] = $shipmentsArray[$admission['invoice_number']];
            }
        }

        return $admissions;
    }

    /**
     * Enriquecer admisiones con datos de auditorías
     */
    public function enrichWithAudits(array $admissions): array
    {
        if (empty($admissions)) {
            return $admissions;
        }

        $admissionNumbers = array_values(array_unique(array_column($admissions, 'number')));

        if (empty($admissionNumbers)) {
            return $admissions;
        }

        $audits = DB::table('audits')
            ->whereIn('admission_number', $admissionNumbers)
            ->get()
            ->keyBy('admission_number');

        $auditsArray = [];
        foreach ($audits as $key => $audit) {
            $auditsArray[$key] = (array) $audit;
        }

        foreach ($admissions as &$admission) {
            $admission['audit'] = $auditsArray[$admission['number']] ?? null;
        }

        return $admissions;
    }

    /**
     * Enriquecer admisiones con datos de admissions_lists
     */
    public function enrichWithAdmissionsLists(array $admissions, string $period): array
    {
        if (empty($admissions)) {
            return $admissions;
        }

        $admissionNumbers = array_values(array_unique(array_column($admissions, 'number')));

        if (empty($admissionNumbers)) {
            return $admissions;
        }

        $admissionsLists = DB::table('admissions_lists')
            ->where('period', $period)
            ->whereIn('admission_number', $admissionNumbers)
            ->get()
            ->keyBy('admission_number');

        $listsArray = [];
        foreach ($admissionsLists as $key => $list) {
            $listsArray[$key] = (array) $list;
        }

        foreach ($admissions as &$admission) {
            if (isset($listsArray[$admission['number']])) {
                $listData = $listsArray[$admission['number']];
                $admission['admissions_list'] = $listData;
                if (!empty($listData['biller'])) {
                    $admission['biller'] = $listData['biller'];
                }
            }
        }

        return $admissions;
    }

    /**
     * Obtener admisiones basadas en el periodo de admissions_lists
     */
    public function getAdmissionsByPeriod(string $period): array
    {
        $periodPattern = strlen($period) === 4 ? $period . '%' : $period;

        $admissionsLists = DB::table('admissions_lists')
            ->where('period', 'LIKE', $periodPattern)
            ->select('admission_number', 'period', 'biller')
            ->get();

        if ($admissionsLists->isEmpty()) {
            return [];
        }

        $listsArray      = [];
        $admissionNumbers = [];
        foreach ($admissionsLists as $list) {
            $listsArray[$list->admission_number] = [
                'period' => $list->period,
                'biller' => $list->biller,
            ];
            $admissionNumbers[] = $list->admission_number;
        }

        $admissionNumbersQuoted = implode(',', array_map(function ($num) {
            return DB::connection('external_db')->getPdo()->quote($num);
        }, $admissionNumbers));

        $sql = "
            SELECT
                a.numero_documento                        AS number,
                a.fecha_hora_atencion                     AS attendance_date,
                a.fecha_hora_atencion                     AS date,
                EXTRACT(MONTH FROM a.fecha_hora_atencion) AS month,
                EXTRACT(YEAR FROM a.fecha_hora_atencion)  AS year,
                a.codigo_paciente                         AS patient_code,
                p.nombre_paciente                         AS patient_name,
                sm.codigo_servicio_medico                 AS service_code,
                sm.nombre_servicio                        AS service_name,
                a.tipo_atencion                           AS type,
                a.total                                   AS amount,
                as2.nombre_aseguradora                    AS insurer_name,
                e.nombre_empresa                          AS company,
                c.numero_factura                          AS invoice_number,
                c.fecha_emision                           AS invoice_date,
                ps.numero_factura                         AS paid_invoice_number,
                d.fecha_devolucion                        AS devolution_date,
                d.numero_factura                          AS devolution_invoice_number,
                CASE
                    WHEN c.numero_factura IS NULL
                        OR (c.numero_factura NOT LIKE '003-%' AND c.numero_factura NOT LIKE '004-%')
                    THEN 'Pendiente'
                    WHEN ps.numero_factura IS NOT NULL
                    THEN 'Pagado'
                    ELSE 'Liquidado'
                END AS status
            FROM sisclin.atenciones a
            LEFT JOIN sisclin.aseguradoras as2 ON a.aseguradora_id = as2.id
            LEFT JOIN sisclin.empresas e ON a.empresa_id = e.id
            LEFT JOIN sisclin.pacientes p ON a.paciente_id = p.id
            LEFT JOIN sisclin.servicios_medicos sm ON a.servicio_medico_id = sm.id
            LEFT JOIN sisclin.devoluciones d ON d.comprobante_id = (
                SELECT id FROM sisclin.comprobantes
                WHERE atencion_id = a.id
                  AND (numero_factura LIKE '003-%' OR numero_factura LIKE '004-%')
                LIMIT 1
            )
            LEFT JOIN (
                SELECT
                    atencion_id,
                    MAX(fecha_emision::text || '|' || numero_factura) AS max_invoice_key
                FROM sisclin.comprobantes
                WHERE numero_factura LIKE '003-%' OR numero_factura LIKE '004-%'
                GROUP BY atencion_id
            ) latest ON a.id = latest.atencion_id
            LEFT JOIN sisclin.comprobantes c ON a.id = c.atencion_id
                AND latest.max_invoice_key IS NOT NULL
                AND (c.fecha_emision::text || '|' || c.numero_factura) = latest.max_invoice_key
            LEFT JOIN sisclin.pagos_seguros ps ON c.numero_factura = ps.numero_factura
            WHERE a.numero_documento IN ({$admissionNumbersQuoted})
                AND a.total >= 0
                AND p.nombre_paciente != ''
                AND p.nombre_paciente != 'No existe...'
                AND as2.nombre_aseguradora NOT IN ('PARTICULAR', 'PACIENTES PARTICULARES')
        ";

        $results = DB::connection('external_db')->select($sql);

        $admissions = [];
        foreach ($results as $item) {
            $admission = (array) $item;

            if (isset($listsArray[$admission['number']])) {
                $admission['biller'] = $listsArray[$admission['number']]['biller'];
                $admission['period'] = $listsArray[$admission['number']]['period'];
            }

            $admissions[] = $admission;
        }

        return $admissions;
    }
}
