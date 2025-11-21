<?php

namespace Tests\Unit;

use App\Services\AggregationService;
use PHPUnit\Framework\TestCase;

class AggregationServiceTest extends TestCase
{
    protected AggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AggregationService();
    }

    /**
     * Test cálculo de estado de facturación por mes
     */
    public function test_calculate_invoice_status_by_month(): void
    {
        $admissions = [
            ['month' => 1, 'status' => 'Pagado', 'amount' => 100.00],
            ['month' => 1, 'status' => 'Pendiente', 'amount' => 50.00],
            ['month' => 2, 'status' => 'Pagado', 'amount' => 200.00],
        ];

        $result = $this->service->calculateInvoiceStatusByMonth($admissions);

        $this->assertArrayHasKey('view_by_quantity', $result);
        $this->assertArrayHasKey('view_by_amount', $result);
        $this->assertEquals(['Ene', 'Feb'], $result['view_by_quantity']['months']);
        $this->assertEquals([1, 1], $result['view_by_quantity']['invoiced']);
        $this->assertEquals([1, 0], $result['view_by_quantity']['pending']);
    }

    /**
     * Test cálculo de estado de pagos
     */
    public function test_calculate_payment_status(): void
    {
        $admissions = [
            ['invoice_number' => '001-123', 'paid_invoice_number' => '001-123', 'amount' => 100.00],
            ['invoice_number' => '001-124', 'paid_invoice_number' => null, 'amount' => 50.00],
            ['invoice_number' => '005-001', 'paid_invoice_number' => null, 'amount' => 30.00], // Temporal, no debe contar
        ];

        $result = $this->service->calculatePaymentStatus($admissions);

        $this->assertArrayHasKey('view_by_quantity', $result);
        $this->assertArrayHasKey('view_by_amount', $result);
        $this->assertEquals(1, $result['view_by_quantity']['paid']);
        $this->assertEquals(1, $result['view_by_quantity']['pending']);
        $this->assertEquals(100.00, $result['view_by_amount']['paid']);
        $this->assertEquals(50.00, $result['view_by_amount']['pending']);
    }

    /**
     * Test cálculo de análisis por tipo de atención
     */
    public function test_calculate_attendance_type_analysis(): void
    {
        $admissions = [
            ['type' => 'EMERGENCIA', 'amount' => 300.00],
            ['type' => 'EMERGENCIA', 'amount' => 300.00],
            ['type' => 'CONSULTA', 'amount' => 100.00],
        ];

        $result = $this->service->calculateAttendanceTypeAnalysis($admissions);

        $this->assertArrayHasKey('view_by_quantity', $result);
        $this->assertArrayHasKey('view_by_amount', $result);

        // El tipo EMERGENCIA debe tener 2 registros
        $emergencia = collect($result['view_by_quantity'])->firstWhere('type', 'EMERGENCIA');
        $this->assertEquals(2, $emergencia['count']);
        $this->assertEquals(66.67, $emergencia['percentage']);
    }

    /**
     * Test cálculo de pacientes únicos
     */
    public function test_calculate_unique_patients(): void
    {
        $admissions = [
            ['patient_code' => 'P001'],
            ['patient_code' => 'P001'], // Duplicado
            ['patient_code' => 'P002'],
            ['patient_code' => 'P003'],
        ];

        $result = $this->service->calculateUniquePatients($admissions);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(75.00, $result['percentage_of_admissions']);
    }

    /**
     * Test cálculo de top empresas
     */
    public function test_calculate_top_companies(): void
    {
        $admissions = [
            ['company' => 'MAPFRE', 'amount' => 100.00],
            ['company' => 'MAPFRE', 'amount' => 200.00],
            ['company' => 'PACIFICO', 'amount' => 150.00],
        ];

        $result = $this->service->calculateTopCompanies($admissions, 2);

        $this->assertArrayHasKey('view_by_quantity', $result);
        $this->assertArrayHasKey('view_by_amount', $result);

        // MAPFRE debe ser el primero por cantidad
        $this->assertEquals('MAPFRE', $result['view_by_quantity'][0]['company']);
        $this->assertEquals(2, $result['view_by_quantity'][0]['count']);

        // MAPFRE debe ser el primero por monto
        $this->assertEquals('MAPFRE', $result['view_by_amount'][0]['company']);
        $this->assertEquals(300.00, $result['view_by_amount'][0]['amount']);
    }

    /**
     * Test cálculo con datos vacíos
     */
    public function test_calculate_with_empty_data(): void
    {
        $admissions = [];

        $result = $this->service->calculateInvoiceStatusByMonth($admissions);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('view_by_quantity', $result);
        $this->assertArrayHasKey('view_by_amount', $result);
    }

    /**
     * Test cálculo de rendimiento de auditores
     */
    public function test_calculate_auditors_performance(): void
    {
        $admissions = [
            [
                'audit' => ['auditor' => 'Dr. Smith'],
                'status_auditor' => 'AUDITADO',
                'amount' => 100.00
            ],
            [
                'audit' => ['auditor' => 'Dr. Smith'],
                'status_auditor' => 'PAGADO',
                'amount' => 200.00
            ],
            [
                'audit' => ['auditor' => 'Dr. Jones'],
                'status_auditor' => 'AUDITADO',
                'amount' => 150.00
            ],
        ];

        $result = $this->service->calculateAuditorsPerformance($admissions);

        $this->assertArrayHasKey('auditors_list', $result);
        $this->assertArrayHasKey('view_by_quantity', $result);
        $this->assertArrayHasKey('view_by_amount', $result);
        $this->assertCount(2, $result['auditors_list']);
    }

    /**
     * Test cálculo de rendimiento de facturadores
     */
    public function test_calculate_billers_performance(): void
    {
        $admissions = [
            [
                'biller' => 'Juan Perez',
                'invoice_number' => '001-123',
                'status_biller' => 'FACTURADO',
                'amount' => 100.00
            ],
            [
                'biller' => 'Juan Perez',
                'invoice_number' => '001-124',
                'status_biller' => 'PAGADO',
                'amount' => 200.00
            ],
        ];

        $result = $this->service->calculateBillersPerformance($admissions);

        $this->assertArrayHasKey('billers_list', $result);
        $this->assertArrayHasKey('view_by_quantity', $result);
        $this->assertArrayHasKey('view_by_amount', $result);
        $this->assertCount(1, $result['billers_list']);
    }

    /**
     * Test cálculo de estadísticas mensuales
     */
    public function test_calculate_monthly_statistics(): void
    {
        $admissions = [
            ['month' => 1, 'patient_code' => 'P001', 'amount' => 100.00, 'invoice_number' => '001-001', 'paid_invoice_number' => '001-001'],
            ['month' => 1, 'patient_code' => 'P001', 'amount' => 150.00, 'invoice_number' => '001-002', 'paid_invoice_number' => null], // Mismo paciente, 2da atención, facturado no pagado
            ['month' => 1, 'patient_code' => 'P002', 'amount' => 200.00, 'invoice_number' => null, 'paid_invoice_number' => null], // No facturado
            ['month' => 2, 'patient_code' => 'P003', 'amount' => 300.00, 'invoice_number' => '001-003', 'paid_invoice_number' => '001-003'],
            ['month' => 2, 'patient_code' => 'P004', 'amount' => 250.00, 'invoice_number' => '001-004', 'paid_invoice_number' => '001-004'],
        ];

        $result = $this->service->calculateMonthlyStatistics($admissions, '2025-01-01', '2025-02-28');

        $this->assertIsArray($result);
        $this->assertCount(2, $result); // 2 meses

        // Verificar estructura del primer mes
        $enero = $result[0];
        $this->assertEquals(1, $enero['month']);
        $this->assertEquals('Ene', $enero['month_name']);
        $this->assertEquals(2, $enero['unique_patients']); // P001 y P002
        $this->assertEquals(3, $enero['total_admissions']); // 3 atenciones
        $this->assertEquals(450.00, $enero['total_amount']); // 100 + 150 + 200
        $this->assertEquals(150.00, $enero['avg_amount_per_admission']); // 450 / 3
        $this->assertEquals(1.5, $enero['avg_admissions_per_patient']); // 3 / 2
        $this->assertEquals(50.00, $enero['recurrence_rate']); // ((3-2)/2) * 100

        // Verificar estructura del segundo mes
        $febrero = $result[1];
        $this->assertEquals(2, $febrero['month']);
        $this->assertEquals('Feb', $febrero['month_name']);
        $this->assertEquals(2, $febrero['unique_patients']); // P003 y P004
        $this->assertEquals(2, $febrero['total_admissions']); // 2 atenciones
        $this->assertEquals(550.00, $febrero['total_amount']); // 300 + 250
        $this->assertEquals(275.00, $febrero['avg_amount_per_admission']); // 550 / 2
        $this->assertEquals(1.00, $febrero['avg_admissions_per_patient']); // 2 / 2
        $this->assertEquals(0.00, $febrero['recurrence_rate']); // ((2-2)/2) * 100
    }

    /**
     * Test estadísticas mensuales con meses sin datos
     */
    public function test_calculate_monthly_statistics_with_empty_months(): void
    {
        $admissions = [
            ['month' => 1, 'patient_code' => 'P001', 'amount' => 100.00],
            ['month' => 3, 'patient_code' => 'P002', 'amount' => 200.00],
        ];

        // Rango de Enero a Marzo (incluye Febrero sin datos)
        $result = $this->service->calculateMonthlyStatistics($admissions, '2025-01-01', '2025-03-31');

        $this->assertCount(3, $result); // Debe incluir Enero, Febrero y Marzo

        // Verificar que Febrero existe pero con valores en cero
        $febrero = $result[1];
        $this->assertEquals(2, $febrero['month']);
        $this->assertEquals('Feb', $febrero['month_name']);
        $this->assertEquals(0, $febrero['unique_patients']);
        $this->assertEquals(0, $febrero['total_admissions']);
        $this->assertEquals(0, $febrero['total_amount']);
    }

    /**
     * Test estadísticas mensuales con rango de varios años
     */
    public function test_calculate_monthly_statistics_cross_year(): void
    {
        $admissions = [
            ['month' => 12, 'patient_code' => 'P001', 'amount' => 100.00],
            ['month' => 1, 'patient_code' => 'P002', 'amount' => 200.00],
        ];

        // Rango de Diciembre 2024 a Enero 2025
        $result = $this->service->calculateMonthlyStatistics($admissions, '2024-12-01', '2025-01-31');

        $this->assertCount(2, $result); // Diciembre y Enero
        $this->assertEquals(12, $result[0]['month']); // Diciembre
        $this->assertEquals(1, $result[1]['month']); // Enero
    }

    /**
     * Test estadísticas mensuales sin datos
     */
    public function test_calculate_monthly_statistics_with_no_data(): void
    {
        $admissions = [];

        $result = $this->service->calculateMonthlyStatistics($admissions, '2025-01-01', '2025-01-31');

        $this->assertCount(1, $result); // Debe retornar Enero con valores en cero
        $this->assertEquals(1, $result[0]['month']);
        $this->assertEquals(0, $result[0]['unique_patients']);
        $this->assertEquals(0, $result[0]['total_admissions']);
        $this->assertEquals(0, $result[0]['total_amount']);
    }
}
