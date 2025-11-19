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
}
