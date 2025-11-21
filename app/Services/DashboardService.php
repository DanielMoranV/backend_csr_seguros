<?php

namespace App\Services;

use App\Repositories\DashboardAdmissionRepository;
use App\Repositories\DashboardAggregationRepository;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        protected DashboardAdmissionRepository $admissionRepository,
        protected DashboardAggregationRepository $aggregationRepository,
        protected AggregationService $aggregationService
    ) {}

    /**
     * Análisis por rango de fechas
     * OPTIMIZADO: Usa agregaciones directas en MySQL en lugar de procesar en PHP
     *
     * @param string $startDate Fecha de inicio (Y-m-d)
     * @param string $endDate Fecha de fin (Y-m-d)
     * @param bool $includeAdmissions Si incluir array completo de admisiones (default: true)
     * @param bool $aggregationsOnly Si solo retornar agregaciones sin admisiones (más rápido)
     */
    public function getDateRangeAnalysis(
        string $startDate,
        string $endDate,
        bool $includeAdmissions = true,
        bool $aggregationsOnly = false
    ): array {
        // OPTIMIZACIÓN: Calcular agregaciones directamente en MySQL
        $aggregations = $this->aggregationRepository->getDateRangeAggregations($startDate, $endDate);

        // Formatear agregaciones para el response
        $result = [
            'summary' => [
                'total_admissions' => $aggregations['total_admissions'],
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
            'invoice_status_by_month' => $this->formatInvoiceStatusByMonth($aggregations['invoice_by_month']),
            'insurers_by_month' => $this->formatInsurersByMonth($aggregations['insurers_by_month']),
            'payment_status' => $this->formatPaymentStatus($aggregations['payment_status'], $aggregations['total_admissions']),
            'attendance_type_analysis' => $this->formatAttendanceTypeAnalysis($aggregations['attendance_type'], $aggregations['total_admissions']),
            'unique_patients' => $this->formatUniquePatients($aggregations['unique_patients'], $aggregations['total_admissions']),
            'top_companies' => $this->formatTopCompanies(
                $aggregations['top_companies_by_count'],
                $aggregations['top_companies_by_amount'],
                $aggregations['total_admissions']
            ),
            'monthly_statistics' => $this->formatMonthlyStatistics($aggregations['monthly_statistics'], $startDate, $endDate),
        ];

        // Si solo se requieren agregaciones, retornar inmediatamente (MUY RÁPIDO)
        if ($aggregationsOnly) {
            return $result;
        }

        // Si se requieren las admisiones completas, traerlas y enriquecerlas
        if ($includeAdmissions) {
            $admissions = $this->admissionRepository->getUniqueAdmissionsByDateRange($startDate, $endDate);
            $admissions = $this->admissionRepository->enrichWithShipments($admissions);
            $result['admissions'] = $admissions;

            // Calcular estadísticas mensuales (pacientes únicos, atenciones y montos por mes)
            $result['monthly_statistics'] = $this->aggregationService->calculateMonthlyStatistics(
                $admissions,
                $startDate,
                $endDate
            );
        }

        return $result;
    }

    /**
     * Formatear estado de facturación por mes
     * NUEVO: Formato de array con 3 estados (Facturado, Pagado, Pendiente)
     */
    protected function formatInvoiceStatusByMonth($data): array
    {
        $byQuantity = [];
        $byAmount = [];

        foreach ($data as $row) {
            $month = $row->month;

            // Estado: Facturado
            $byQuantity[] = [
                'status' => 'Facturado',
                'month' => $month,
                'count' => $row->invoiced_count ?? 0,
            ];

            $byAmount[] = [
                'status' => 'Facturado',
                'month' => $month,
                'amount' => round($row->invoiced_amount ?? 0, 2),
            ];

            // Estado: Pagado
            $byQuantity[] = [
                'status' => 'Pagado',
                'month' => $month,
                'count' => $row->paid_count ?? 0,
            ];

            $byAmount[] = [
                'status' => 'Pagado',
                'month' => $month,
                'amount' => round($row->paid_amount ?? 0, 2),
            ];

            // Estado: Pendiente
            $byQuantity[] = [
                'status' => 'Pendiente',
                'month' => $month,
                'count' => $row->pending_count ?? 0,
            ];

            $byAmount[] = [
                'status' => 'Pendiente',
                'month' => $month,
                'amount' => round($row->pending_amount ?? 0, 2),
            ];
        }

        return [
            'view_by_quantity' => $byQuantity,
            'view_by_amount' => $byAmount,
        ];
    }

    /**
     * Formatear aseguradoras por mes
     */
    protected function formatInsurersByMonth($data): array
    {
        $byQuantity = [];
        $byAmount = [];

        foreach ($data as $row) {
            $byQuantity[] = [
                'insurance' => $row->insurer_name,
                'month' => $row->month,
                'count' => $row->count,
            ];
            $byAmount[] = [
                'insurance' => $row->insurer_name,
                'month' => $row->month,
                'count' => round($row->amount, 2),
            ];
        }

        return [
            'view_by_quantity' => $byQuantity,
            'view_by_amount' => $byAmount,
        ];
    }

    /**
     * Formatear estado de pagos
     */
    protected function formatPaymentStatus($data, $totalAdmissions): array
    {
        return [
            'view_by_quantity' => [
                'paid' => $data->paid_count ?? 0,
                'pending' => $data->pending_count ?? 0,
            ],
            'view_by_amount' => [
                'paid' => round($data->paid_amount ?? 0, 2),
                'pending' => round($data->pending_amount ?? 0, 2),
            ],
        ];
    }

    /**
     * Formatear análisis por tipo de atención
     */
    protected function formatAttendanceTypeAnalysis($data, $totalAdmissions): array
    {
        $byQuantity = [];
        $byAmount = [];
        $totalAmount = collect($data)->sum('amount');

        foreach ($data as $row) {
            $byQuantity[] = [
                'type' => ucwords(strtolower($row->type)),
                'count' => $row->count,
                'percentage' => $totalAdmissions > 0 ? round(($row->count * 100) / $totalAdmissions, 2) : 0,
            ];
            $byAmount[] = [
                'type' => ucwords(strtolower($row->type)),
                'amount' => round($row->amount, 2),
                'average' => round($row->average, 2),
                'percentage' => $totalAmount > 0 ? round(($row->amount * 100) / $totalAmount, 2) : 0,
            ];
        }

        return [
            'view_by_quantity' => $byQuantity,
            'view_by_amount' => $byAmount,
        ];
    }

    /**
     * Formatear pacientes únicos
     */
    protected function formatUniquePatients($uniquePatients, $totalAdmissions): array
    {
        return [
            'total' => $uniquePatients,
            'percentage_of_admissions' => $totalAdmissions > 0
                ? round(($uniquePatients * 100) / $totalAdmissions, 2)
                : 0,
        ];
    }

    /**
     * Formatear top empresas
     */
    protected function formatTopCompanies($byCount, $byAmount, $totalAdmissions): array
    {
        $totalAmount = collect($byAmount)->sum('amount');

        $byQuantity = [];
        foreach ($byCount as $row) {
            $byQuantity[] = [
                'company' => $row->company,
                'count' => $row->count,
                'percentage' => $totalAdmissions > 0 ? round(($row->count * 100) / $totalAdmissions, 2) : 0,
            ];
        }

        $byAmountFormatted = [];
        foreach ($byAmount as $row) {
            $byAmountFormatted[] = [
                'company' => $row->company,
                'amount' => round($row->amount, 2),
                'percentage' => $totalAmount > 0 ? round(($row->amount * 100) / $totalAmount, 2) : 0,
            ];
        }

        return [
            'view_by_quantity' => $byQuantity,
            'view_by_amount' => $byAmountFormatted,
        ];
    }

    /**
     * Análisis por periodo
     * Basado en admissions_lists: Solo reportes de auditores y facturadores
     *
     * @param string $period Formato: YYYYMM (mes) o YYYY (año)
     * @return array
     */
    public function getPeriodAnalysis(string $period): array
    {
        // Determinar rango de fechas para contexto
        if (strlen($period) === 4) { // Year only
            $year = $period;
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear()->format('Y-m-d');
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear()->format('Y-m-d');
        } else { // Year and month
            $year = substr($period, 0, 4);
            $month = substr($period, 4, 2);

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
        }

        // 1. Obtener admisiones desde admissions_lists usando LIKE para periodo
        $admissions = $this->admissionRepository->getAdmissionsByPeriod($period);

        // 2. Enriquecer con datos de auditorías
        $admissions = $this->admissionRepository->enrichWithAudits($admissions);

        // 3. Enriquecer con datos de envíos
        $admissions = $this->admissionRepository->enrichWithShipments($admissions);

        // 4. Procesar estados de auditores y facturadores
        $admissions = $this->processAuditorsAndBillers($admissions);

        // 5. Calcular SOLO rendimiento de auditores y facturadores
        $auditorsPerformance = $this->aggregationService->calculateAuditorsPerformance($admissions);
        $billersPerformance = $this->aggregationService->calculateBillersPerformance($admissions);

        return [
            'summary' => [
                'total_admissions' => count($admissions),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
            'auditors_performance' => $auditorsPerformance,
            'billers_performance' => $billersPerformance,
        ];
    }

    /**
     * Análisis por periodo CON admisiones
     * Variante del método getPeriodAnalysis que incluye el array completo de admisiones
     * Útil para exportaciones a Excel
     *
     * @param string $period Formato: YYYYMM (mes) o YYYY (año)
     * @return array
     */
    public function getPeriodAnalysisWithAdmissions(string $period): array
    {
        // Determinar rango de fechas para contexto
        if (strlen($period) === 4) { // Year only
            $year = $period;
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear()->format('Y-m-d');
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear()->format('Y-m-d');
        } else { // Year and month
            $year = substr($period, 0, 4);
            $month = substr($period, 4, 2);

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
        }

        // 1. Obtener admisiones desde admissions_lists usando LIKE para periodo
        $admissions = $this->admissionRepository->getAdmissionsByPeriod($period);

        // 2. Enriquecer con datos de auditorías
        $admissions = $this->admissionRepository->enrichWithAudits($admissions);

        // 3. Enriquecer con datos de envíos
        $admissions = $this->admissionRepository->enrichWithShipments($admissions);

        // 4. Procesar estados de auditores y facturadores
        $admissions = $this->processAuditorsAndBillers($admissions);

        // 5. Calcular SOLO rendimiento de auditores y facturadores
        $auditorsPerformance = $this->aggregationService->calculateAuditorsPerformance($admissions);
        $billersPerformance = $this->aggregationService->calculateBillersPerformance($admissions);

        return [
            'summary' => [
                'total_admissions' => count($admissions),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
            'auditors_performance' => $auditorsPerformance,
            'billers_performance' => $billersPerformance,
            'admissions' => $admissions, // INCLUIR admisiones para exportación
        ];
    }

    /**
     * Procesar estados de auditores y facturadores
     */
    protected function processAuditorsAndBillers(array $admissions): array
    {
        foreach ($admissions as &$admission) {
            // Normalizar facturas temporales
            if (isset($admission['invoice_number']) && preg_match('/^00[56]-/', $admission['invoice_number'])) {
                $admission['invoice_number'] = '';
            }

            // Determinar si es devoluci�n
            $admission['is_devolution'] = !empty($admission['devolution_date'])
                && isset($admission['devolution_invoice_number'])
                && $admission['devolution_invoice_number'] === $admission['invoice_number'];

            // Estado del auditor
            if (!empty($admission['audit'])) {
                $admission['status_auditor'] = !empty($admission['paid_invoice_number'])
                    ? 'PAGADO'
                    : ($admission['is_devolution'] ? 'DEVOLUCION' : 'AUDITADO');
            }

            // Estado del facturador
            if (!empty($admission['biller']) && !empty($admission['invoice_number'])) {
                if (!empty($admission['paid_invoice_number'])) {
                    $admission['status_biller'] = 'PAGADO';
                } elseif ($admission['is_devolution']) {
                    $admission['status_biller'] = 'DEVOLUCION';
                } elseif (!empty($admission['verified_shipment_date'])) {
                    $admission['status_biller'] = 'ENVIADO';
                } else {
                    $admission['status_biller'] = 'FACTURADO';
                }
            }
        }

        return $admissions;
    }

    /**
     * Formatear estadísticas mensuales
     */
    protected function formatMonthlyStatistics($data, string $startDate, string $endDate): array
    {
        $monthsEs = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        // Crear un array indexado por mes con los datos de MySQL
        $statsByMonth = [];
        foreach ($data as $row) {
            $statsByMonth[$row->month] = $row;
        }

        // Determinar todos los meses del rango
        $startMonth = (int)date('n', strtotime($startDate));
        $startYear = (int)date('Y', strtotime($startDate));
        $endMonth = (int)date('n', strtotime($endDate));
        $endYear = (int)date('Y', strtotime($endDate));

        $result = [];
        $currentYear = $startYear;
        $currentMonth = $startMonth;

        while (($currentYear < $endYear) || ($currentYear === $endYear && $currentMonth <= $endMonth)) {
            $stats = $statsByMonth[$currentMonth] ?? null;

            $uniquePatients = $stats ? $stats->unique_patients : 0;
            $totalAdmissions = $stats ? $stats->total_admissions : 0;
            $totalAmount = $stats ? $stats->total_amount : 0;

            $result[] = [
                'month' => $currentMonth,
                'month_name' => $monthsEs[$currentMonth] ?? (string)$currentMonth,
                'unique_patients' => $uniquePatients,
                'total_admissions' => $totalAdmissions,
                'total_amount' => round($totalAmount, 2),
                'avg_amount_per_admission' => $totalAdmissions > 0
                    ? round($totalAmount / $totalAdmissions, 2)
                    : 0,
                'avg_admissions_per_patient' => $uniquePatients > 0
                    ? round($totalAdmissions / $uniquePatients, 2)
                    : 0,
                'recurrence_rate' => $uniquePatients > 0
                    ? round((($totalAdmissions - $uniquePatients) / $uniquePatients) * 100, 2)
                    : 0,
            ];

            $currentMonth++;
            if ($currentMonth > 12) {
                $currentMonth = 1;
                $currentYear++;
            }
        }

        return $result;
    }

    /**
     * Obtener etiqueta del periodo en espa�ol
     */
    protected function getPeriodLabel(string $period): string
    {
        $year = substr($period, 0, 4);

        if (strlen($period) === 4) {
            return $year;
        }

        $month = (int)substr($period, 4, 2);

        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $months[$month] . ' ' . $year;
    }
}
