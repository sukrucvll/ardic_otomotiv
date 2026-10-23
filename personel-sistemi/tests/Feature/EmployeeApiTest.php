<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(array $abilities = ['employees:read'], $expiresAt = null): string
    {
        return ApiClient::create(['name' => 'Eğitim Portalı'])
            ->createToken('service-token', $abilities, $expiresAt)
            ->plainTextToken;
    }

    public function test_request_without_token_is_rejected(): void
    {
        $this->getJson('/api/v1/employees')->assertUnauthorized();
    }

    public function test_request_with_invalid_token_is_rejected(): void
    {
        $this->withToken('1|gecersiz-token')->getJson('/api/v1/employees')->assertUnauthorized();
    }

    public function test_token_without_read_ability_is_forbidden(): void
    {
        $token = $this->tokenFor(['something:else']);

        $this->withToken($token)->getJson('/api/v1/employees')->assertForbidden();
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = $this->tokenFor(expiresAt: now()->subMinute());

        $this->withToken($token)->getJson('/api/v1/employees')->assertUnauthorized();
    }

    public function test_revoked_token_is_rejected(): void
    {
        $token = $this->tokenFor();
        ApiClient::first()->tokens()->delete();

        $this->withToken($token)->getJson('/api/v1/employees')->assertUnauthorized();
    }

    public function test_employees_can_be_listed_with_valid_token(): void
    {
        Employee::factory()->count(3)->create();

        $this->withToken($this->tokenFor())
            ->getJson('/api/v1/employees')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'first_name', 'last_name', 'full_name', 'email', 'department']],
                'links',
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_employees_can_be_filtered(): void
    {
        Employee::factory()->create(['first_name' => 'Ayşe', 'department' => 'Kalite']);
        Employee::factory()->create(['first_name' => 'Mehmet', 'department' => 'Üretim']);

        $token = $this->tokenFor();

        $this->withToken($token)->getJson('/api/v1/employees?department=Kalite')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Ayşe');

        $this->withToken($token)->getJson('/api/v1/employees?search=Mehm')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Mehmet');
    }

    public function test_single_employee_can_be_fetched(): void
    {
        $employee = Employee::factory()->create();

        $this->withToken($this->tokenFor())
            ->getJson("/api/v1/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('data.email', $employee->email);
    }

    public function test_deleted_employee_is_not_exposed(): void
    {
        $employee = Employee::factory()->create();
        $employee->delete();

        $this->withToken($this->tokenFor())
            ->getJson("/api/v1/employees/{$employee->id}")
            ->assertNotFound();
    }

    public function test_departments_endpoint_returns_counts(): void
    {
        Employee::factory()->count(2)->create(['department' => 'Kalite']);

        $this->withToken($this->tokenFor())
            ->getJson('/api/v1/departments')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Kalite', 'employee_count' => 2]);
    }

    public function test_api_is_rate_limited(): void
    {
        config(['personnel.api.rate_limit' => 2]);
        $token = $this->tokenFor();

        $this->withToken($token)->getJson('/api/v1/departments')->assertOk();
        $this->withToken($token)->getJson('/api/v1/departments')->assertOk();
        $this->withToken($token)->getJson('/api/v1/departments')->assertTooManyRequests();
    }

    public function test_tokens_are_stored_hashed(): void
    {
        $token = $this->tokenFor();
        [, $secret] = explode('|', $token, 2);

        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $secret]);
        $this->assertDatabaseHas('personal_access_tokens', ['token' => hash('sha256', $secret)]);
    }
}
