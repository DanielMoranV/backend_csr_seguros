<?php

namespace App\Services;

use App\Repositories\DashboardAdmissionRepository;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        protected DashboardAdmissionRepository $admissionRepository,
        protected AggregationService $aggregationService
    ) {}

    /**
     * An�lisis por rango de fechas
     */
    public function getDateRangeAnalysis(string $startDate, string $endDate): array
    {
        // 1. Obtener admisiones deduplicadas de la base de datos legada
        $admissions = $this->admissionRepository->getUniqueAdmissionsByDateRange(
            $startDate,
            $endDate
        );

        // 2. Enriquecer con datos de env�os (base de datos aplicaci�n)
        $admissions = $this->admissionRepository->enrichWithShipments($admissions);

        // 3. Generar agregaciones
        $invoiceStatusByMonth = $this->aggregationService->calculateInvoiceStatusByMonth($admissions);
        $insurersByMonth = $this->aggregationService->calculateInsurersByMonth($admissions);
        $paymentStatus = $this->aggregationService->calculatePaymentStatus($admissions);
        $attendanceTypeAnalysis = $this->aggregationService->calculateAttendanceTypeAnalysis($admissions);
        $uniquePatients = $this->aggregationService->calculateUniquePatients($admissions);
        $topCompanies = $this->aggregationService->calculateTopCompanies($admissions);

        return [
            'summary' => [
                'total_admissions' => count($admissions),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ],
            'invoice_status_by_month' => $invoiceStatusByMonth,
            'insurers_by_month' => $insurersByMonth,
            'payment_status' => $paymentStatus,
            'attendance_type_analysis' => $attendanceTypeAnalysis,
            'unique_patients' => $uniquePatients,
            'top_companies' => $topCompanies,
            'admissions' => $admissions,
        ];
    }

    /**
     * An�lisis por periodo
     */
    public function getPeriodAnalysis(string $period): array
    {
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

        // 1. Obtener admisiones
        $admissions = $this->admissionRepository->getUniqueAdmissionsByDateRange(
            $startDate,
            $endDate
        );

        // 2. Enriquecer con datos de admissions_lists (facturadores y auditores)
        $admissions = $this->admissionRepository->enrichWithAdmissionsLists($admissions, $period);

        // 3. Enriquecer con auditor�as y env�os
        $admissions = $this->admissionRepository->enrichWithAudits($admissions);
        $admissions = $this->admissionRepository->enrichWithShipments($admissions);

        // 4. Procesar estados de auditores y facturadores
        $admissions = $this->processAuditorsAndBillers($admissions);

        // 5. Generar agregaciones
        $auditorsPerformance = $this->aggregationService->calculateAuditorsPerformance($admissions);
        $billersPerformance = $this->aggregationService->calculateBillersPerformance($admissions);

        return [
            'summary' => [
                'total_admissions' => count($admissions),
                'period' => $period,
                'period_label' => $this->getPeriodLabel($period),
            ],
            'auditors_performance' => $auditorsPerformance,
            'billers_performance' => $billersPerformance,
            'admissions' => $admissions,
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
