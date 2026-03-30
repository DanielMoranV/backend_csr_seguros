<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear rol admin si no existe
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin', 'guard_name' => 'api']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_migrate_admissions_validation_fails_without_numbers()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/custom-migrations/admissions', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['admission_numbers']);
    }

    public function test_migrate_admissions_success()
    {
        Http::fake([
            '*/api/v1/custom-migrations/admissions' => Http::response(['job_id' => '123', 'status' => 'pending'], 200),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/custom-migrations/admissions', [
                'admission_numbers' => ['240001', '240002'],
                'dry_run' => false
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'job_id' => '123',
                'status' => 'pending'
            ]
        ]);
    }

    public function test_migrate_admissions_external_api_fails()
    {
        Http::fake([
            '*/api/v1/custom-migrations/admissions' => Http::response(['error' => 'Not found'], 404),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/custom-migrations/admissions', [
                'admission_numbers' => ['999999'],
            ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => true, // ApiResponseClass::sendResponse sets success:true even for 404
            'status' => 404,
            'data' => [
                'error' => 'Not found'
            ]
        ]);
    }
}
