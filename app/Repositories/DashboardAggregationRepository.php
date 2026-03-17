<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Repositorio para cálculo de agregaciones en PostgreSQL (schema sisclin).
 * Factura válida = serie 003 o 004 (política de facturación a aseguradoras).
 */
class DashboardAggregationRepository
{
    /**
     * Calcular todas las agregaciones para análisis de rango de fechas
     */
    public function getDateRangeAggregations(string $startDate, string $endDate): array
    {
        $baseWhere = function ($query) use ($startDate, $endDate) {
            return $query
                ->whereBetween('a.fecha_hora_atencion', [$startDate, $endDate])
                ->where('a.total', '>=', 0)
                ->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('sisclin.pacientes as p2')
                        ->whereColumn('p2.id', 'a.paciente_id')
                        ->where('p2.nombre_paciente', '!=', '')
                        ->where('p2.nombre_paciente', '!=', 'No existe...');
                });
        };

        // 1. Estado de facturación por mes
        $invoiceByMonth = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->leftJoin('sisclin.comprobantes as c', function ($join) {
                $join->on('a.id', '=', 'c.atencion_id')
                    ->where(function ($q) {
                        $q->where('c.numero_factura', 'LIKE', '003-%')
                          ->orWhere('c.numero_factura', 'LIKE', '004-%');
                    });
            })
            ->leftJoin('sisclin.pagos_seguros as ps', 'c.numero_factura', '=', 'ps.numero_factura')
            ->where($baseWhere)
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw("
                EXTRACT(MONTH FROM a.fecha_hora_atencion) AS month,

                COUNT(DISTINCT CASE
                    WHEN c.numero_factura IS NULL THEN a.id
                END) AS pending_count,

                COUNT(DISTINCT CASE
                    WHEN c.numero_factura IS NOT NULL THEN a.id
                END) AS invoiced_count,

                COUNT(DISTINCT CASE
                    WHEN c.numero_factura IS NOT NULL AND ps.numero_factura IS NOT NULL THEN a.id
                END) AS paid_count,

                SUM(CASE WHEN c.numero_factura IS NULL THEN a.total ELSE 0 END) AS pending_amount,
                SUM(CASE WHEN c.numero_factura IS NOT NULL THEN a.total ELSE 0 END) AS invoiced_amount,
                SUM(CASE WHEN c.numero_factura IS NOT NULL AND ps.numero_factura IS NOT NULL THEN a.total ELSE 0 END) AS paid_amount
            ")
            ->groupByRaw('EXTRACT(MONTH FROM a.fecha_hora_atencion)')
            ->orderBy('month')
            ->get();

        // 2. Aseguradoras por mes
        $insurersByMonth = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->where($baseWhere)
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw("
                as2.nombre_aseguradora AS insurer_name,
                EXTRACT(MONTH FROM a.fecha_hora_atencion) AS month,
                COUNT(*) AS count,
                SUM(a.total) AS amount
            ")
            ->groupByRaw("as2.nombre_aseguradora, EXTRACT(MONTH FROM a.fecha_hora_atencion)")
            ->orderBy('month')
            ->orderByDesc('count')
            ->get();

        // 3. Estado de pagos totales del periodo
        $paymentStatus = (object) [
            'paid_count'     => $invoiceByMonth->sum('paid_count'),
            'paid_amount'    => $invoiceByMonth->sum('paid_amount'),
            'pending_count'  => $invoiceByMonth->sum('pending_count'),
            'pending_amount' => $invoiceByMonth->sum('pending_amount'),
        ];

        // 4. Análisis por tipo de atención
        $attendanceType = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->where($baseWhere)
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw("
                UPPER(TRIM(a.tipo_atencion)) AS type,
                COUNT(*) AS count,
                SUM(a.total) AS amount,
                AVG(a.total) AS average
            ")
            ->groupByRaw('UPPER(TRIM(a.tipo_atencion))')
            ->orderByDesc('count')
            ->get();

        // 5. Pacientes únicos y total de admisiones
        $summary = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->where($baseWhere)
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('
                COUNT(DISTINCT a.paciente_id) AS unique_patients,
                COUNT(*) AS total_admissions
            ')
            ->first();

        // 6. Top 10 empresas por cantidad
        $topCompaniesByCount = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.empresas as e', 'a.empresa_id', '=', 'e.id')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->where($baseWhere)
            ->whereNotNull('e.nombre_empresa')
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('e.nombre_empresa AS company, COUNT(*) AS count, SUM(a.total) AS amount')
            ->groupBy('e.nombre_empresa')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // 7. Top 10 empresas por monto
        $topCompaniesByAmount = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.empresas as e', 'a.empresa_id', '=', 'e.id')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->where($baseWhere)
            ->whereNotNull('e.nombre_empresa')
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw('e.nombre_empresa AS company, COUNT(*) AS count, SUM(a.total) AS amount')
            ->groupBy('e.nombre_empresa')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        // 8. Estadísticas mensuales
        $monthlyStatistics = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->where($baseWhere)
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw("
                EXTRACT(MONTH FROM a.fecha_hora_atencion) AS month,
                COUNT(DISTINCT a.paciente_id) AS unique_patients,
                COUNT(*) AS total_admissions,
                SUM(a.total) AS total_amount
            ")
            ->groupByRaw('EXTRACT(MONTH FROM a.fecha_hora_atencion)')
            ->orderBy('month')
            ->get();

        // 9. Desglose por tipo de atención por mes
        $attendanceTypeByMonth = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->where($baseWhere)
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->selectRaw("
                EXTRACT(MONTH FROM a.fecha_hora_atencion) AS month,
                UPPER(TRIM(a.tipo_atencion)) AS type,
                COUNT(*) AS count,
                COUNT(DISTINCT a.paciente_id) AS unique_patients,
                SUM(a.total) AS amount,
                AVG(a.total) AS average
            ")
            ->groupByRaw("EXTRACT(MONTH FROM a.fecha_hora_atencion), UPPER(TRIM(a.tipo_atencion))")
            ->orderBy('month')
            ->orderByDesc('count')
            ->get();

        return [
            'invoice_by_month'         => $invoiceByMonth,
            'insurers_by_month'        => $insurersByMonth,
            'payment_status'           => $paymentStatus,
            'attendance_type'          => $attendanceType,
            'unique_patients'          => $summary->unique_patients,
            'total_admissions'         => $summary->total_admissions,
            'top_companies_by_count'   => $topCompaniesByCount,
            'top_companies_by_amount'  => $topCompaniesByAmount,
            'monthly_statistics'       => $monthlyStatistics,
            'attendance_type_by_month' => $attendanceTypeByMonth,
        ];
    }

    /**
     * Calcular agregaciones para análisis de periodo
     */
    public function getPeriodAggregations(string $startDate, string $endDate, string $period): array
    {
        $admissionNumbers = DB::connection('external_db')
            ->table('sisclin.atenciones as a')
            ->leftJoin('sisclin.aseguradoras as as2', 'a.aseguradora_id', '=', 'as2.id')
            ->whereBetween('a.fecha_hora_atencion', [$startDate, $endDate])
            ->where('a.total', '>=', 0)
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('sisclin.pacientes as p2')
                    ->whereColumn('p2.id', 'a.paciente_id')
                    ->where('p2.nombre_paciente', '!=', '')
                    ->where('p2.nombre_paciente', '!=', 'No existe...');
            })
            ->whereNotIn('as2.nombre_aseguradora', ['PARTICULAR', 'PACIENTES PARTICULARES'])
            ->pluck('a.numero_documento')
            ->toArray();

        if (empty($admissionNumbers)) {
            return [
                'auditors_list'        => [],
                'auditors_performance' => [],
                'billers_list'         => [],
                'billers_performance'  => [],
            ];
        }

        $auditorsData = DB::table('audits')
            ->whereIn('admission_number', $admissionNumbers)
            ->groupBy('auditor')
            ->orderBy('auditor')
            ->pluck('auditor')
            ->toArray();

        $billersData = DB::table('admissions_lists')
            ->where('period', $period)
            ->whereIn('admission_number', $admissionNumbers)
            ->groupBy('biller')
            ->orderBy('biller')
            ->pluck('biller')
            ->toArray();

        return [
            'auditors_list'    => $auditorsData,
            'billers_list'     => $billersData,
            'admission_numbers' => $admissionNumbers,
        ];
    }
}
