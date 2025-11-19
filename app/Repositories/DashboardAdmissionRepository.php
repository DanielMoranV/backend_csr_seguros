<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardAdmissionRepository
{
    /**
     * Obtener admisiones deduplicadas con factura más reciente
     * Utiliza ROW_NUMBER() para seleccionar la factura más reciente por admisión
     */
    public function getUniqueAdmissionsByDateRange(string $startDate, string $endDate): array
    {
        // Construir el query base con todas las relaciones necesarias
        $baseQuery = $this->buildBaseQuery()
            ->whereBetween('SC0011.fec_doc', [$startDate, $endDate])
            ->where('SC0011.tot_doc', '>=', 0)
            ->where('SC0011.nom_pac', '!=', '')
            ->where('SC0011.nom_pac', '!=', 'No existe...')
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES']);

        // Traemos todas las admisiones (potencialmente con duplicados por factura)
        $allAdmissions = $baseQuery->get();

        // Agrupamos por número de admisión y seleccionamos la mejor factura en PHP
        $uniqueAdmissions = collect($allAdmissions)
            ->groupBy('number')
            ->map(function ($admissionsGroup) {
                return $admissionsGroup->sortByDesc('invoice_date')
                    ->sortBy(function ($admission) {
                        // El invoice_number puede ser null, hay que manejarlo
                        $invoice_number = $admission->invoice_number ?? '';
                        return (str_starts_with($invoice_number, '005-') || str_starts_with($invoice_number, '006-')) ? 1 : 0;
                    })
                    ->first();
            })
            ->values()
            ->all();

        return json_decode(json_encode($uniqueAdmissions), true);
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

        return json_decode(json_encode($baseQuery->get()), true);
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
        $invoiceNumbers = collect($admissions)
            ->pluck('invoice_number')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($invoiceNumbers)) {
            return $admissions;
        }

        // Consultar tabla shipments en la base de datos de aplicación
        $shipments = DB::table('shipments')
            ->whereIn('invoice_number', $invoiceNumbers)
            ->whereNotNull('verified_shipment_date')
            ->get()
            ->keyBy('invoice_number');

        foreach ($admissions as &$admission) {
            if (isset($admission['invoice_number']) && isset($shipments[$admission['invoice_number']])) {
                // Si tiene envío verificado y aún no está clasificado como Pagado/Devolución
                if ($admission['status'] === 'Liquidado') {
                    $admission['status'] = 'Enviado';
                }
                $admission['verified_shipment_date'] = $shipments[$admission['invoice_number']]->verified_shipment_date;
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
        $admissionNumbers = collect($admissions)
            ->pluck('number')
            ->unique()
            ->values()
            ->toArray();

        if (empty($admissionNumbers)) {
            return $admissions;
        }

        // Consultar tabla audits en la base de datos de aplicación
        $audits = DB::table('audits')
            ->whereIn('admission_number', $admissionNumbers)
            ->get()
            ->keyBy('admission_number');

        foreach ($admissions as &$admission) {
            $admission['audit'] = isset($audits[$admission['number']])
                ? json_decode(json_encode($audits[$admission['number']]), true)
                : null;
        }

        return $admissions;
    }

    /**
     * Enriquecer admisiones con datos de admissions_lists (MySQL Aplicación)
     * Obtiene información de facturadores y auditores por periodo
     */
    public function enrichWithAdmissionsLists(array $admissions, string $period): array
    {
        $admissionNumbers = collect($admissions)
            ->pluck('number')
            ->unique()
            ->values()
            ->toArray();

        if (empty($admissionNumbers)) {
            return $admissions;
        }

        // Consultar tabla admissions_lists en la base de datos de aplicación
        $admissionsLists = DB::table('admissions_lists')
            ->where('period', $period)
            ->whereIn('admission_number', $admissionNumbers)
            ->get()
            ->keyBy('admission_number');

        foreach ($admissions as &$admission) {
            if (isset($admissionsLists[$admission['number']])) {
                $listData = json_decode(json_encode($admissionsLists[$admission['number']]), true);
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
