<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class DashboardTest extends TestCase
{
    /**
     * Test análisis por rango de fechas con autenticación
     */
    public function test_date_range_analysis_returns_valid_structure(): void
    {
        // Crear usuario autenticado
        $user = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/dashboard/date-range-analysis', [
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'total_admissions',
                        'period' => ['start', 'end'],
                    ],
                    'invoice_status_by_month' => [
                        'view_by_quantity' => ['months', 'invoiced', 'pending'],
                        'view_by_amount' => ['months', 'invoiced', 'pending'],
                    ],
                    'insurers_by_month',
                    'payment_status',
                    'attendance_type_analysis',
                    'unique_patients',
                    'top_companies',
                    'admissions',
                ],
                'message',
            ]);
    }

    /**
     * Test validación de fechas inválidas
     */
    public function test_date_range_validation_fails_with_invalid_dates(): void
    {
        $user = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/dashboard/date-range-analysis', [
                'start_date' => '2025-01-31',
                'end_date' => '2025-01-01', // Fecha fin antes que inicio
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /**
     * Test validación de rango máximo de 1 año
     */
    public function test_date_range_validation_fails_with_range_greater_than_one_year(): void
    {
        $user = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/dashboard/date-range-analysis', [
                'start_date' => '2023-01-01',
                'end_date' => '2025-01-31', // Más de 1 año
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_range']);
    }

    /**
     * Test análisis por periodo
     */
    public function test_period_analysis_returns_valid_structure(): void
    {
        $user = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/dashboard/period-analysis', [
                'period' => '202501',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'total_admissions',
                        'period',
                        'period_label',
                    ],
                    'auditors_performance',
                    'billers_performance',
                    'admissions',
                ],
                'message',
            ]);
    }

    /**
     * Test validación de periodo inválido
     */
    public function test_period_validation_fails_with_invalid_format(): void
    {
        $user = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/dashboard/period-analysis', [
                'period' => '202513', // Mes inválido
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    /**
     * Test acceso no autorizado
     */
    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->postJson('/api/dashboard/date-range-analysis', [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test limpiar caché
     */
    public function test_clear_cache_endpoint_works(): void
    {
        $user = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/dashboard/clear-cache');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['cache_cleared'],
                'message',
            ]);
    }
}
