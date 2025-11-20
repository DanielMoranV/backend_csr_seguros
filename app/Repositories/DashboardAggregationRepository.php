<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Repositorio para cálculo de agregaciones directamente en MySQL
 * Optimizado para evitar transferir datos innecesarios a PHP
 */
class DashboardAggregationRepository
{
    /**
     * Calcular todas las agregaciones para análisis de rango de fechas
     * OPTIMIZACIÓN: Todo se calcula en MySQL, no en PHP
     *
     * @param string $startDate Fecha inicio (Y-m-d)
     * @param string $endDate Fecha fin (Y-m-d)
     * @return array Agregaciones calculadas
     */
    public function getDateRangeAggregations(string $startDate, string $endDate): array
    {
        // Filtros base que se reutilizan
        $baseWhere = function ($query) use ($startDate, $endDate) {
            return $query
                ->whereBetween('SC0011.fec_doc', [$startDate, $endDate])
                ->where('SC0011.tot_doc', '>=', 0)
                ->where('SC0011.nom_pac', '!=', '')
                ->where('SC0011.nom_pac', '!=', 'No existe...');
        };

        // 1. Estado de facturación por mes (cantidad y monto)
        $invoiceByMonth = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0017', 'SC0011.num_doc', '=', 'SC0017.num_doc')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                MONTH(SC0011.fec_doc) as month,
                SUM(CASE
                    WHEN SC0017.num_fac IS NULL
                        OR SC0017.num_fac LIKE "005-%"
                        OR SC0017.num_fac LIKE "006-%"
                    THEN 1 ELSE 0
                END) as pending_count,
                SUM(CASE
                    WHEN SC0017.num_fac IS NOT NULL
                        AND SC0017.num_fac NOT LIKE "005-%"
                        AND SC0017.num_fac NOT LIKE "006-%"
                    THEN 1 ELSE 0
                END) as invoiced_count,
                SUM(CASE
                    WHEN SC0017.num_fac IS NULL
                        OR SC0017.num_fac LIKE "005-%"
                        OR SC0017.num_fac LIKE "006-%"
                    THEN SC0011.tot_doc ELSE 0
                END) as pending_amount,
                SUM(CASE
                    WHEN SC0017.num_fac IS NOT NULL
                        AND SC0017.num_fac NOT LIKE "005-%"
                        AND SC0017.num_fac NOT LIKE "006-%"
                    THEN SC0011.tot_doc ELSE 0
                END) as invoiced_amount
            ')
            ->groupBy(DB::raw('MONTH(SC0011.fec_doc)'))
            ->orderBy('month')
            ->get();

        // 2. Aseguradoras por mes (cantidad y monto)
        $insurersByMonth = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                SC0002.nom_cia as insurer_name,
                MONTH(SC0011.fec_doc) as month,
                COUNT(*) as count,
                SUM(SC0011.tot_doc) as amount
            ')
            ->groupBy('SC0002.nom_cia', DB::raw('MONTH(SC0011.fec_doc)'))
            ->orderBy('month')
            ->orderByDesc('count')
            ->get();

        // 3. Estado de pagos (todas las admisiones)
        // SC0017: facturas emitidas (formato num_fac: "004-0001234", "003-0001234")
        // SC0022: pagos realizados (formato num_fac: "F004-0001234", diferente a SC0017)
        // Campo común: num_doc (SC0011.num_doc = SC0017.num_doc = SC0022.num_doc)
        // Lógica: Si SC0011.num_doc existe en SC0022, la factura está pagada

        // Usar subquery para agrupar por num_doc y evitar duplicados
        $paidDocsSubquery = DB::connection('external_db')
            ->table('SC0011')
            ->join('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->join('SC0017', 'SC0011.num_doc', '=', 'SC0017.num_doc')
            ->join('SC0022', 'SC0017.num_doc', '=', 'SC0022.num_doc')
            ->whereBetween('SC0011.fec_doc', [$startDate, $endDate])
            ->where('SC0011.tot_doc', '>=', 0)
            ->where('SC0011.nom_pac', '!=', '')
            ->where('SC0011.nom_pac', '!=', 'No existe...')
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->where(function($q) {
                $q->where('SC0017.num_fac', 'LIKE', '004-%')
                  ->orWhere('SC0017.num_fac', 'LIKE', '003-%');
            })
            ->whereNotLike('SC0017.num_fac', '005-%')
            ->whereNotLike('SC0017.num_fac', '006-%')
            ->whereNotLike('SC0017.num_fac', '009-%')
            ->groupBy('SC0011.num_doc', 'SC0011.tot_doc')
            ->selectRaw('SC0011.num_doc, SC0011.tot_doc');

        $paidDocs = DB::connection('external_db')
            ->table(DB::raw("({$paidDocsSubquery->toSql()}) as paid"))
            ->mergeBindings($paidDocsSubquery)
            ->selectRaw('COUNT(*) as count, SUM(tot_doc) as amount')
            ->first();

        $totalValidInvoicesSubquery = DB::connection('external_db')
            ->table('SC0011')
            ->join('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->join('SC0017', 'SC0011.num_doc', '=', 'SC0017.num_doc')
            ->whereBetween('SC0011.fec_doc', [$startDate, $endDate])
            ->where('SC0011.tot_doc', '>=', 0)
            ->where('SC0011.nom_pac', '!=', '')
            ->where('SC0011.nom_pac', '!=', 'No existe...')
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->where(function($q) {
                $q->where('SC0017.num_fac', 'LIKE', '004-%')
                  ->orWhere('SC0017.num_fac', 'LIKE', '003-%');
            })
            ->whereNotLike('SC0017.num_fac', '005-%')
            ->whereNotLike('SC0017.num_fac', '006-%')
            ->whereNotLike('SC0017.num_fac', '009-%')
            ->groupBy('SC0011.num_doc', 'SC0011.tot_doc')
            ->selectRaw('SC0011.num_doc, SC0011.tot_doc');

        $totalValidInvoices = DB::connection('external_db')
            ->table(DB::raw("({$totalValidInvoicesSubquery->toSql()}) as total"))
            ->mergeBindings($totalValidInvoicesSubquery)
            ->selectRaw('COUNT(*) as count, SUM(tot_doc) as amount')
            ->first();

        $paymentStatus = (object)[
            'paid_count' => $paidDocs->count ?? 0,
            'paid_amount' => $paidDocs->amount ?? 0,
            'pending_count' => ($totalValidInvoices->count ?? 0) - ($paidDocs->count ?? 0),
            'pending_amount' => ($totalValidInvoices->amount ?? 0) - ($paidDocs->amount ?? 0),
        ];

        // 4. Análisis por tipo de atención
        $attendanceType = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                UPPER(TRIM(SC0011.ta_doc)) as type,
                COUNT(*) as count,
                SUM(SC0011.tot_doc) as amount,
                AVG(SC0011.tot_doc) as average
            ')
            ->groupBy(DB::raw('UPPER(TRIM(SC0011.ta_doc))'))
            ->orderByDesc('count')
            ->get();

        // 5. Pacientes únicos y total de admisiones
        $summary = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                COUNT(DISTINCT SC0011.cod_pac) as unique_patients,
                COUNT(*) as total_admissions
            ')
            ->first();

        // 6. Top 10 empresas por cantidad y monto
        $topCompaniesByCount = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0003', 'SC0011.cod_emp', '=', 'SC0003.cod_emp')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotNull('SC0003.nom_emp')
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                SC0003.nom_emp as company,
                COUNT(*) as count,
                SUM(SC0011.tot_doc) as amount
            ')
            ->groupBy('SC0003.nom_emp')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $topCompaniesByAmount = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0003', 'SC0011.cod_emp', '=', 'SC0003.cod_emp')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotNull('SC0003.nom_emp')
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                SC0003.nom_emp as company,
                COUNT(*) as count,
                SUM(SC0011.tot_doc) as amount
            ')
            ->groupBy('SC0003.nom_emp')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        return [
            'invoice_by_month' => $invoiceByMonth,
            'insurers_by_month' => $insurersByMonth,
            'payment_status' => $paymentStatus,
            'attendance_type' => $attendanceType,
            'unique_patients' => $summary->unique_patients,
            'total_admissions' => $summary->total_admissions,
            'top_companies_by_count' => $topCompaniesByCount,
            'top_companies_by_amount' => $topCompaniesByAmount,
        ];
    }

    /**
     * Calcular agregaciones para análisis de periodo (con auditores y facturadores)
     *
     * @param string $startDate
     * @param string $endDate
     * @param string $period Formato YYYYMM
     * @return array
     */
    public function getPeriodAggregations(string $startDate, string $endDate, string $period): array
    {
        // Obtener números de admisión del periodo para cruzar con tablas de aplicación
        $admissionNumbers = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->whereBetween('SC0011.fec_doc', [$startDate, $endDate])
            ->where('SC0011.tot_doc', '>=', 0)
            ->where('SC0011.nom_pac', '!=', '')
            ->where('SC0011.nom_pac', '!=', 'No existe...')
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->pluck('SC0011.num_doc')
            ->toArray();

        if (empty($admissionNumbers)) {
            return [
                'auditors_list' => [],
                'auditors_performance' => [],
                'billers_list' => [],
                'billers_performance' => [],
            ];
        }

        // Auditores: Obtener de tabla audits
        $auditorsData = DB::table('audits')
            ->whereIn('admission_number', $admissionNumbers)
            ->selectRaw('auditor')
            ->groupBy('auditor')
            ->orderBy('auditor')
            ->pluck('auditor')
            ->toArray();

        // Facturadores: Obtener de admissions_lists
        $billersData = DB::table('admissions_lists')
            ->where('period', $period)
            ->whereIn('admission_number', $admissionNumbers)
            ->selectRaw('biller')
            ->groupBy('biller')
            ->orderBy('biller')
            ->pluck('biller')
            ->toArray();

        return [
            'auditors_list' => $auditorsData,
            'billers_list' => $billersData,
            'admission_numbers' => $admissionNumbers, // Para procesar rendimiento después
        ];
    }
}
