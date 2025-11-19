<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardAdmissionRepository
{
    /**
     * Obtener admisiones deduplicadas con factura más reciente
     * OPTIMIZADO: Usa Window Functions de MySQL 8.1 para deduplicar en base de datos
     */
    public function getUniqueAdmissionsByDateRange(string $startDate, string $endDate): array
    {
        // OPTIMIZACIÓN: Deduplicación con ROW_NUMBER() en MySQL 8.1
        // Esto evita traer datos duplicados a PHP y procesarlos allí

        // Escapar valores para evitar SQL injection
        $startDateQuoted = DB::connection('external_db')->getPdo()->quote($startDate);
        $endDateQuoted = DB::connection('external_db')->getPdo()->quote($endDate);

        $sql = "
            SELECT *
            FROM (
                SELECT
                    SC0011.num_doc as number,
                    SC0011.fec_doc as attendance_date,
                    SC0011.nom_pac as patient,
                    SC0011.hi_doc as attendance_hour,
                    SC0011.ta_doc as type,
                    SC0011.tot_doc as amount,
                    SC0011.cod_pac as patient_code,
                    SC0011.clos_doc as is_closed,
                    SC0003.nom_emp as company,
                    SC0006.nom_ser as doctor,
                    SC0004.nh_pac as medical_record_number,
                    SC0017.num_fac as invoice_number,
                    SC0017.fec_fac as invoice_date,
                    SC0017.uc_sis as biller,
                    SC0033.fh_dev as devolution_date,
                    SC0033.num_fac as devolution_invoice_number,
                    SC0002.nom_cia as insurer_name,
                    SC0022.num_fac as paid_invoice_number,
                    MONTH(SC0011.fec_doc) as month,
                    DATEDIFF(CURDATE(), SC0011.fec_doc) as days_passed,
                    CASE
                        WHEN SC0017.num_fac IS NULL
                            OR SC0017.num_fac LIKE '005-%'
                            OR SC0017.num_fac LIKE '006-%'
                        THEN 'Pendiente'

                        WHEN SC0033.fh_dev IS NOT NULL
                            AND SC0022.num_fac IS NULL
                        THEN 'Devolución'

                        WHEN SC0022.num_fac IS NOT NULL
                        THEN 'Pagado'

                        ELSE 'Liquidado'
                    END as status,
                    ROW_NUMBER() OVER (
                        PARTITION BY SC0011.num_doc
                        ORDER BY
                            SC0017.fec_fac DESC,
                            CASE
                                WHEN SC0017.num_fac LIKE '005-%' OR SC0017.num_fac LIKE '006-%'
                                THEN 1
                                ELSE 0
                            END ASC
                    ) as row_num
                FROM SC0011
                LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
                LEFT JOIN SC0022 ON SC0017.num_fac = SC0022.num_fac
                LEFT JOIN SC0033 ON SC0011.num_doc = SC0033.num_doc
                LEFT JOIN SC0006 ON SC0011.cod_ser = SC0006.cod_ser
                LEFT JOIN SC0002 ON LEFT(SC0011.cod_emp, 2) = SC0002.cod_cia
                LEFT JOIN SC0003 ON SC0011.cod_emp = SC0003.cod_emp
                LEFT JOIN SC0004 ON SC0011.cod_pac = SC0004.cod_pac
                WHERE SC0011.fec_doc BETWEEN {$startDateQuoted} AND {$endDateQuoted}
                    AND SC0011.tot_doc >= 0
                    AND SC0011.nom_pac != ''
                    AND SC0011.nom_pac != 'No existe...'
                    AND SC0002.nom_cia NOT IN ('PARTICULAR', 'PACIENTES PARTICULARES')
            ) as deduplicated
            WHERE row_num = 1
        ";

        $results = DB::connection('external_db')->select($sql);

        // Convertir stdClass a array
        return array_map(fn($item) => (array) $item, $results);
    }

    /**
     * Obtener admisiones SOLO para agregaciones (sin enriquecimiento)
     * OPTIMIZADO: Query mínimo solo con campos necesarios para cálculos
     */
    public function getAdmissionsForAggregation(string $startDate, string $endDate): array
    {
        // Escapar valores para evitar SQL injection
        $startDateQuoted = DB::connection('external_db')->getPdo()->quote($startDate);
        $endDateQuoted = DB::connection('external_db')->getPdo()->quote($endDate);

        // Query ultra optimizado: solo campos para agregaciones
        $sql = "
            SELECT
                SC0011.num_doc as number,
                MONTH(SC0011.fec_doc) as month,
                SC0011.ta_doc as type,
                SC0011.tot_doc as amount,
                SC0011.cod_pac as patient_code,
                SC0003.nom_emp as company,
                SC0002.nom_cia as insurer_name,
                SC0017.num_fac as invoice_number,
                SC0022.num_fac as paid_invoice_number,
                CASE
                    WHEN SC0017.num_fac IS NULL
                        OR SC0017.num_fac LIKE '005-%'
                        OR SC0017.num_fac LIKE '006-%'
                    THEN 'Pendiente'
                    WHEN SC0022.num_fac IS NOT NULL
                    THEN 'Pagado'
                    ELSE 'Liquidado'
                END as status
            FROM SC0011
            LEFT JOIN SC0003 ON SC0011.cod_emp = SC0003.cod_emp
            LEFT JOIN SC0002 ON LEFT(SC0011.cod_emp, 2) = SC0002.cod_cia
            LEFT JOIN SC0017 ON SC0011.num_doc = SC0017.num_doc
            LEFT JOIN SC0022 ON SC0017.num_fac = SC0022.num_fac
            WHERE SC0011.fec_doc BETWEEN {$startDateQuoted} AND {$endDateQuoted}
                AND SC0011.tot_doc >= 0
                AND SC0011.nom_pac != ''
                AND SC0011.nom_pac != 'No existe...'
                AND SC0002.nom_cia NOT IN ('PARTICULAR', 'PACIENTES PARTICULARES')
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
            ->whereIn('SC0011.num_doc', $numbers)
            ->orderByDesc('SC0011.num_doc');

        return $baseQuery->get()->map(fn($item) => (array) $item)->all();
    }

    /**
     * Query base con todos los joins necesarios
     * Incluye todas las tablas del sistema legado SC00XX
     */
    protected function buildBaseQuery()
    {
        return DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0017', 'SC0011.num_doc', '=', 'SC0017.num_doc')
            ->leftJoin('SC0022', 'SC0017.num_doc', '=', 'SC0022.num_doc')
            ->leftJoin('SC0033', 'SC0011.num_doc', '=', 'SC0033.num_doc')
            ->leftJoin('SC0006', 'SC0011.cod_ser', '=', 'SC0006.cod_ser')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->leftJoin('SC0003', 'SC0011.cod_emp', '=', 'SC0003.cod_emp')
            ->leftJoin('SC0004', 'SC0011.cod_pac', '=', 'SC0004.cod_pac')
            ->select([
                'SC0011.num_doc as number',
                'SC0011.fec_doc as attendance_date',
                'SC0011.nom_pac as patient',
                'SC0011.hi_doc as attendance_hour',
                'SC0011.ta_doc as type',
                'SC0011.tot_doc as amount',
                'SC0011.cod_pac as patient_code',
                'SC0011.clos_doc as is_closed',
                'SC0003.nom_emp as company',
                'SC0006.nom_ser as doctor',
                'SC0004.nh_pac as medical_record_number',
                'SC0017.num_fac as invoice_number',
                'SC0017.fec_fac as invoice_date',
                'SC0017.uc_sis as biller',
                'SC0033.fh_dev as devolution_date',
                'SC0033.num_fac as devolution_invoice_number',
                'SC0002.nom_cia as insurer_name',
                'SC0022.num_fac as paid_invoice_number',
                // Campos calculados
                DB::raw('MONTH(SC0011.fec_doc) as month'),
                DB::raw('DATEDIFF(CURDATE(), SC0011.fec_doc) as days_passed'),
                // CASE para determinar el estado de facturación
                DB::raw('
                    CASE
                        WHEN SC0017.num_fac IS NULL
                            OR SC0017.num_fac LIKE "005-%"
                            OR SC0017.num_fac LIKE "006-%"
                        THEN "Pendiente"

                        WHEN SC0033.fh_dev IS NOT NULL
                            AND SC0022.num_fac IS NULL
                        THEN "Devolución"

                        WHEN SC0022.num_fac IS NOT NULL
                        THEN "Pagado"

                        ELSE "Liquidado"
                    END as status
                ')
            ]);
    }

    /**
     * Enriquecer admisiones con datos de envíos (MySQL Aplicación)
     * Consulta la tabla shipments para determinar si una admisión fue enviada
     */
    public function enrichWithShipments(array $admissions): array
    {
        if (empty($admissions)) {
            return $admissions;
        }

        // Optimización: Usar array_column en lugar de Collection
        $invoiceNumbers = array_values(array_unique(array_filter(array_column($admissions, 'invoice_number'))));

        if (empty($invoiceNumbers)) {
            return $admissions;
        }

        // Consultar tabla shipments en la base de datos de aplicación
        // Optimización: Seleccionar solo campos necesarios
        $shipments = DB::table('shipments')
            ->select('invoice_number', 'verified_shipment_date')
            ->whereIn('invoice_number', $invoiceNumbers)
            ->whereNotNull('verified_shipment_date')
            ->get()
            ->keyBy('invoice_number');

        // Optimización: Convertir a array nativo para acceso más rápido
        $shipmentsArray = [];
        foreach ($shipments as $key => $shipment) {
            $shipmentsArray[$key] = $shipment->verified_shipment_date;
        }

        foreach ($admissions as &$admission) {
            if (isset($admission['invoice_number']) && isset($shipmentsArray[$admission['invoice_number']])) {
                // Si tiene envío verificado y aún no está clasificado como Pagado/Devolución
                if ($admission['status'] === 'Liquidado') {
                    $admission['status'] = 'Enviado';
                }
                $admission['verified_shipment_date'] = $shipmentsArray[$admission['invoice_number']];
            }
        }

        return $admissions;
    }

    /**
     * Enriquecer admisiones con datos de auditorías (MySQL Aplicación)
     * Consulta la tabla audits para obtener información de auditoría
     */
    public function enrichWithAudits(array $admissions): array
    {
        if (empty($admissions)) {
            return $admissions;
        }

        // Optimización: Usar array_column en lugar de Collection
        $admissionNumbers = array_values(array_unique(array_column($admissions, 'number')));

        if (empty($admissionNumbers)) {
            return $admissions;
        }

        // Consultar tabla audits en la base de datos de aplicación
        $audits = DB::table('audits')
            ->whereIn('admission_number', $admissionNumbers)
            ->get()
            ->keyBy('admission_number');

        // Optimización: Evitar json_decode(json_encode()), convertir directamente
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
     * Enriquecer admisiones con datos de admissions_lists (MySQL Aplicación)
     * Obtiene información de facturadores y auditores por periodo
     */
    public function enrichWithAdmissionsLists(array $admissions, string $period): array
    {
        if (empty($admissions)) {
            return $admissions;
        }

        // Optimización: Usar array_column en lugar de Collection
        $admissionNumbers = array_values(array_unique(array_column($admissions, 'number')));

        if (empty($admissionNumbers)) {
            return $admissions;
        }

        // Consultar tabla admissions_lists en la base de datos de aplicación
        $admissionsLists = DB::table('admissions_lists')
            ->where('period', $period)
            ->whereIn('admission_number', $admissionNumbers)
            ->get()
            ->keyBy('admission_number');

        // Optimización: Evitar json_decode(json_encode()), convertir directamente
        $listsArray = [];
        foreach ($admissionsLists as $key => $list) {
            $listsArray[$key] = (array) $list;
        }

        foreach ($admissions as &$admission) {
            if (isset($listsArray[$admission['number']])) {
                $listData = $listsArray[$admission['number']];
                $admission['admissions_list'] = $listData;
                // Sobrescribir biller si existe en admissions_lists
                if (!empty($listData['biller'])) {
                    $admission['biller'] = $listData['biller'];
                }
            }
        }

        return $admissions;
    }
}
