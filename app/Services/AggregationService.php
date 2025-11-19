<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AggregationService
{
    protected array $monthsEs = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ];

    /**
     * Calcular estado de facturaci�n por mes
     * Optimizado: Una sola iteración en lugar de múltiples groupBy
     */
    public function calculateInvoiceStatusByMonth(array $admissions): array
    {
        // Optimización: Calcular cantidad y monto en una sola iteración
        $byMonth = [];

        foreach ($admissions as $admission) {
            $month = $admission['month'] ?? 0;
            if (!isset($byMonth[$month])) {
                $byMonth[$month] = [
                    'invoiced_count' => 0,
                    'pending_count' => 0,
                    'invoiced_amount' => 0,
                    'pending_amount' => 0,
                ];
            }

            $isPending = ($admission['status'] ?? '') === 'Pendiente';
            $amount = $admission['amount'] ?? 0;

            if ($isPending) {
                $byMonth[$month]['pending_count']++;
                $byMonth[$month]['pending_amount'] += $amount;
            } else {
                $byMonth[$month]['invoiced_count']++;
                $byMonth[$month]['invoiced_amount'] += $amount;
            }
        }

        ksort($byMonth);

        $months = array_map(fn($m) => $this->monthsEs[$m] ?? $m, array_keys($byMonth));
        $invoicedCounts = array_column($byMonth, 'invoiced_count');
        $pendingCounts = array_column($byMonth, 'pending_count');
        $invoicedAmounts = array_column($byMonth, 'invoiced_amount');
        $pendingAmounts = array_column($byMonth, 'pending_amount');

        return [
            'view_by_quantity' => [
                'months' => $months,
                'invoiced' => $invoicedCounts,
                'pending' => $pendingCounts,
            ],
            'view_by_amount' => [
                'months' => $months,
                'invoiced' => $invoicedAmounts,
                'pending' => $pendingAmounts,
            ],
        ];
    }

    /**
     * Calcular aseguradoras por mes
     */
    public function calculateInsurersByMonth(array $admissions): array
    {
        $collection = collect($admissions);

        // Por cantidad
        $byQuantity = $collection
            ->groupBy(fn($item) => $item['insurer_name'] . '|' . $item['month'])
            ->map(function ($items, $key) {
                [$insurer, $month] = explode('|', $key);
                return [
                    'insurance' => $insurer,
                    'month' => (int)$month,
                    'count' => $items->count(),
                ];
            })
            ->sortBy([['month', 'asc'], ['count', 'desc']])
            ->values();

        // Por monto
        $byAmount = $collection
            ->groupBy(fn($item) => $item['insurer_name'] . '|' . $item['month'])
            ->map(function ($items, $key) {
                [$insurer, $month] = explode('|', $key);
                return [
                    'insurance' => $insurer,
                    'month' => (int)$month,
                    'count' => $items->sum('amount'),
                ];
            })
            ->sortBy([['month', 'asc'], ['count', 'desc']])
            ->values();

        return [
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }

    /**
     * Calcular estado de pagos
     */
    public function calculatePaymentStatus(array $admissions): array
    {
        $collection = collect($admissions);

        $validInvoices = $collection->filter(function ($item) {
            return isset($item['invoice_number'])
                && !empty($item['invoice_number'])
                && !str_starts_with($item['invoice_number'], '005-')
                && !str_starts_with($item['invoice_number'], '006-');
        });

        return [
            'view_by_quantity' => [
                'paid' => $validInvoices->whereNotNull('paid_invoice_number')->count(),
                'pending' => $validInvoices->whereNull('paid_invoice_number')->count(),
            ],
            'view_by_amount' => [
                'paid' => $validInvoices->whereNotNull('paid_invoice_number')->sum('amount'),
                'pending' => $validInvoices->whereNull('paid_invoice_number')->sum('amount'),
            ],
        ];
    }

    /**
     * Calcular an�lisis por tipo de atenci�n
     */
    public function calculateAttendanceTypeAnalysis(array $admissions): array
    {
        $collection = collect($admissions);
        $totalAdmissions = $collection->count();
        $totalAmount = $collection->sum('amount');

        $groupByCallback = function ($item) {
            return strtoupper(trim($item['type']));
        };

        // Por cantidad
        $byQuantity = $collection
            ->groupBy($groupByCallback)
            ->map(function ($items, $type) use ($totalAdmissions) {
                $count = $items->count();
                return [
                    'type' => ucwords(strtolower($type)),
                    'count' => $count,
                    'percentage' => $totalAdmissions > 0 ? round(($count * 100) / $totalAdmissions, 2) : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();

        // Por monto con promedio
        $byAmount = $collection
            ->groupBy($groupByCallback)
            ->map(function ($items, $type) use ($totalAmount) {
                $amount = $items->sum('amount');
                return [
                    'type' => ucwords(strtolower($type)),
                    'amount' => round($amount, 2),
                    'average' => round($items->avg('amount'), 2),
                    'percentage' => $totalAmount > 0 ? round(($amount * 100) / $totalAmount, 2) : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return [
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }

    /**
     * Calcular pacientes �nicos
     */
    public function calculateUniquePatients(array $admissions): array
    {
        $collection = collect($admissions);
        $totalAdmissions = $collection->count();
        $uniquePatients = $collection->pluck('patient_code')->filter()->unique()->count();

        return [
            'total' => $uniquePatients,
            'percentage_of_admissions' => $totalAdmissions > 0
                ? round(($uniquePatients * 100) / $totalAdmissions, 2)
                : 0,
        ];
    }

    /**
     * Calcular top 10 empresas
     * Optimizado: Una sola iteración en lugar de dos groupBy
     */
    public function calculateTopCompanies(array $admissions, int $limit = 10): array
    {
        // Optimización: Calcular todo en una sola iteración
        $companies = [];
        $totalAdmissions = 0;
        $totalAmount = 0;

        foreach ($admissions as $admission) {
            if (empty($admission['company'])) {
                continue;
            }

            $company = $admission['company'];
            $amount = $admission['amount'] ?? 0;

            if (!isset($companies[$company])) {
                $companies[$company] = ['count' => 0, 'amount' => 0];
            }

            $companies[$company]['count']++;
            $companies[$company]['amount'] += $amount;
            $totalAdmissions++;
            $totalAmount += $amount;
        }

        // Ordenar por cantidad y tomar top
        uasort($companies, fn($a, $b) => $b['count'] <=> $a['count']);
        $topByQuantity = array_slice($companies, 0, $limit, true);

        // Ordenar por monto y tomar top
        uasort($companies, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $topByAmount = array_slice($companies, 0, $limit, true);

        // Formatear resultados
        $byQuantity = [];
        foreach ($topByQuantity as $company => $data) {
            $byQuantity[] = [
                'company' => $company,
                'count' => $data['count'],
                'percentage' => $totalAdmissions > 0 ? round(($data['count'] * 100) / $totalAdmissions, 2) : 0,
            ];
        }

        $byAmount = [];
        foreach ($topByAmount as $company => $data) {
            $byAmount[] = [
                'company' => $company,
                'amount' => round($data['amount'], 2),
                'percentage' => $totalAmount > 0 ? round(($data['amount'] * 100) / $totalAmount, 2) : 0,
            ];
        }

        return [
            'view_by_quantity' => $byQuantity,
            'view_by_amount' => $byAmount,
        ];
    }

    /**
     * Calcular rendimiento de auditores
     */
    public function calculateAuditorsPerformance(array $admissions): array
    {
        $collection = collect($admissions)->filter(fn($item) => !empty($item['audit']));

        if ($collection->isEmpty()) {
            return [
                'auditors_list' => [],
                'view_by_quantity' => [],
                'view_by_amount' => [],
            ];
        }

        $auditors = $collection->pluck('audit.auditor')->filter()->unique()->sort()->values();

        $byQuantity = $collection
            ->filter(fn($item) => !empty($item['status_auditor']))
            ->groupBy(fn($item) => $item['audit']['auditor'] . '|' . $item['status_auditor'])
            ->map(function ($items, $key) {
                [$auditor, $status] = explode('|', $key);
                return [
                    'auditor' => $auditor,
                    'status' => $status,
                    'count' => $items->count(),
                ];
            })
            ->sortBy('auditor')
            ->values();

        $byAmount = $collection
            ->filter(fn($item) => !empty($item['status_auditor']))
            ->groupBy(fn($item) => $item['audit']['auditor'] . '|' . $item['status_auditor'])
            ->map(function ($items, $key) {
                [$auditor, $status] = explode('|', $key);
                return [
                    'auditor' => $auditor,
                    'status' => $status,
                    'count' => $items->sum('amount'),
                ];
            })
            ->sortBy('auditor')
            ->values();

        return [
            'auditors_list' => $auditors->toArray(),
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }

    /**
     * Calcular rendimiento de facturadores
     */
    public function calculateBillersPerformance(array $admissions): array
    {
        $collection = collect($admissions)
            ->filter(fn($item) => !empty($item['biller']))
            ->filter(fn($item) =>
                isset($item['invoice_number'])
                && !empty($item['invoice_number'])
                && !str_starts_with($item['invoice_number'], '005-')
                && !str_starts_with($item['invoice_number'], '006-')
            );

        if ($collection->isEmpty()) {
            return [
                'billers_list' => [],
                'view_by_quantity' => [],
                'view_by_amount' => [],
            ];
        }

        $billers = $collection->pluck('biller')->filter()->unique()->sort()->values();

        $byQuantity = $collection
            ->filter(fn($item) => !empty($item['status_biller']))
            ->groupBy(fn($item) => $item['biller'] . '|' . $item['status_biller'])
            ->map(function ($items, $key) {
                [$biller, $status] = explode('|', $key);
                return [
                    'biller' => $biller,
                    'status' => $status,
                    'count' => $items->count(),
                ];
            })
            ->sortBy('biller')
            ->values();

        $byAmount = $collection
            ->filter(fn($item) => !empty($item['status_biller']))
            ->groupBy(fn($item) => $item['biller'] . '|' . $item['status_biller'])
            ->map(function ($items, $key) {
                [$biller, $status] = explode('|', $key);
                return [
                    'biller' => $biller,
                    'status' => $status,
                    'count' => $items->sum('amount'),
                ];
            })
            ->sortBy('biller')
            ->values();

        return [
            'billers_list' => $billers->toArray(),
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
        ];
    }
}
