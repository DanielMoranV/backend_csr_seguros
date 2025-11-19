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
     */
    public function calculateInvoiceStatusByMonth(array $admissions): array
    {
        $collection = collect($admissions);

        // Por cantidad
        $byQuantity = $collection->groupBy('month')->map(function ($items, $month) {
            return [
                'invoiced' => $items->where('status', '!=', 'Pendiente')->count(),
                'pending' => $items->where('status', 'Pendiente')->count(),
            ];
        });

        // Por monto
        $byAmount = $collection->groupBy('month')->map(function ($items, $month) {
            return [
                'invoiced' => $items->where('status', '!=', 'Pendiente')->sum('amount'),
                'pending' => $items->where('status', 'Pendiente')->sum('amount'),
            ];
        });

        $months = $collection->pluck('month')->unique()->sort()->map(fn($m) => $this->monthsEs[$m])->values();

        return [
            'view_by_quantity' => [
                'months' => $months->toArray(),
                'invoiced' => $byQuantity->pluck('invoiced')->values()->toArray(),
                'pending' => $byQuantity->pluck('pending')->values()->toArray(),
            ],
            'view_by_amount' => [
                'months' => $months->toArray(),
                'invoiced' => $byAmount->pluck('invoiced')->values()->toArray(),
                'pending' => $byAmount->pluck('pending')->values()->toArray(),
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
     */
    public function calculateTopCompanies(array $admissions, int $limit = 10): array
    {
        $collection = collect($admissions)->filter(fn($item) => !empty($item['company']));
        $totalAdmissions = $collection->count();
        $totalAmount = $collection->sum('amount');

        // Top por cantidad
        $byQuantity = $collection
            ->groupBy('company')
            ->map(function ($items, $company) use ($totalAdmissions) {
                $count = $items->count();
                return [
                    'company' => $company,
                    'count' => $count,
                    'percentage' => $totalAdmissions > 0 ? round(($count * 100) / $totalAdmissions, 2) : 0,
                ];
            })
            ->sortByDesc('count')
            ->take($limit)
            ->values();

        // Top por monto
        $byAmount = $collection
            ->groupBy('company')
            ->map(function ($items, $company) use ($totalAmount) {
                $amount = $items->sum('amount');
                return [
                    'company' => $company,
                    'amount' => round($amount, 2),
                    'percentage' => $totalAmount > 0 ? round(($amount * 100) / $totalAmount, 2) : 0,
                ];
            })
            ->sortByDesc('amount')
            ->take($limit)
            ->values();

        return [
            'view_by_quantity' => $byQuantity->toArray(),
            'view_by_amount' => $byAmount->toArray(),
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
