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
        // NUEVO: Incluye 3 estados - Facturado, Pagado, Pendiente
        $invoiceByMonth = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0017', 'SC0011.num_doc', '=', 'SC0017.num_doc')
            ->leftJoin('SC0022', 'SC0017.num_doc', '=', 'SC0022.num_doc')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                MONTH(SC0011.fec_doc) as month,

                -- Pendiente: Sin facturar o con factura temporal
                COUNT(DISTINCT CASE
                    WHEN SC0017.num_fac IS NULL
                        OR SC0017.num_fac LIKE "005-%"
                        OR SC0017.num_fac LIKE "006-%"
                        OR SC0017.num_fac LIKE "009-%"
                    THEN SC0011.num_doc
                END) as pending_count,

                -- Facturado: Con factura válida (incluye pagadas y no pagadas)
                COUNT(DISTINCT CASE
                    WHEN SC0017.num_fac IS NOT NULL
                        AND SC0017.num_fac NOT LIKE "005-%"
                        AND SC0017.num_fac NOT LIKE "006-%"
                        AND SC0017.num_fac NOT LIKE "009-%"
                    THEN SC0011.num_doc
                END) as invoiced_count,

                -- Pagado: Facturado Y registrado en SC0022
                COUNT(DISTINCT CASE
                    WHEN SC0017.num_fac IS NOT NULL
                        AND SC0017.num_fac NOT LIKE "005-%"
                        AND SC0017.num_fac NOT LIKE "006-%"
                        AND SC0017.num_fac NOT LIKE "009-%"
                        AND SC0022.num_doc IS NOT NULL
                    THEN SC0011.num_doc
                END) as paid_count,

                -- Montos
                SUM(CASE
                    WHEN SC0017.num_fac IS NULL
                        OR SC0017.num_fac LIKE "005-%"
                        OR SC0017.num_fac LIKE "006-%"
                        OR SC0017.num_fac LIKE "009-%"
                    THEN SC0011.tot_doc ELSE 0
                END) as pending_amount,

                SUM(CASE
                    WHEN SC0017.num_fac IS NOT NULL
                        AND SC0017.num_fac NOT LIKE "005-%"
                        AND SC0017.num_fac NOT LIKE "006-%"
                        AND SC0017.num_fac NOT LIKE "009-%"
                    THEN SC0011.tot_doc ELSE 0
                END) as invoiced_amount,

                SUM(CASE
                    WHEN SC0017.num_fac IS NOT NULL
                        AND SC0017.num_fac NOT LIKE "005-%"
                        AND SC0017.num_fac NOT LIKE "006-%"
                        AND SC0017.num_fac NOT LIKE "009-%"
                        AND SC0022.num_doc IS NOT NULL
                    THEN SC0011.tot_doc ELSE 0
                END) as paid_amount
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
        // OPTIMIZACIÓN: Calculado directamente desde invoice_by_month para evitar
        // queries pesadas con múltiples JOINs que causan "MySQL server has gone away"
        // Los datos de paid/pending ya están calculados por mes en $invoiceByMonth,
        // solo necesitamos sumarlos para obtener el total del periodo
        $paymentStatus = (object)[
            'paid_count' => $invoiceByMonth->sum('paid_count'),
            'paid_amount' => $invoiceByMonth->sum('paid_amount'),
            'pending_count' => $invoiceByMonth->sum('pending_count'),
            'pending_amount' => $invoiceByMonth->sum('pending_amount'),
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

        // 7. Estadísticas mensuales (pacientes únicos, atenciones totales, monto total por mes)
        // Optimizado: Todo calculado directamente en MySQL
        // Solo métricas de pacientes y atenciones (sin facturación)
        $monthlyStatistics = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                MONTH(SC0011.fec_doc) as month,
                COUNT(DISTINCT SC0011.cod_pac) as unique_patients,
                COUNT(*) as total_admissions,
                SUM(SC0011.tot_doc) as total_amount
            ')
            ->groupBy(DB::raw('MONTH(SC0011.fec_doc)'))
            ->orderBy('month')
            ->get();

        // 8. Desglose por tipo de atención por mes
        // Para cada mes: cantidad, monto, promedio y porcentaje por tipo de atención
        $attendanceTypeByMonth = DB::connection('external_db')
            ->table('SC0011')
            ->leftJoin('SC0002', DB::raw('LEFT(SC0011.cod_emp, 2)'), '=', 'SC0002.cod_cia')
            ->where($baseWhere)
            ->whereNotIn('SC0002.nom_cia', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                MONTH(SC0011.fec_doc) as month,
                UPPER(TRIM(SC0011.ta_doc)) as type,
                COUNT(*) as count,
                COUNT(DISTINCT SC0011.cod_pac) as unique_patients,
                SUM(SC0011.tot_doc) as amount,
                AVG(SC0011.tot_doc) as average
            ')
            ->groupBy(DB::raw('MONTH(SC0011.fec_doc)'), DB::raw('UPPER(TRIM(SC0011.ta_doc))'))
            ->orderBy('month')
            ->orderByDesc('count')
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
            'monthly_statistics' => $monthlyStatistics,
            'attendance_type_by_month' => $attendanceTypeByMonth,
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
