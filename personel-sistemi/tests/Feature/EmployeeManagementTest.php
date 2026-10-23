<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_employee_list_is_displayed(): void
    {
        $employee = Employee::factory()->create();

        $this->get('/employees')->assertOk()->assertSee($employee->full_name);
    }

    public function test_employee_list_renders_when_empty(): void
    {
        $this->get('/employees')->assertOk()->assertSee('Kriterlere uygun personel bulunamadı.');
    }

    public function test_employee_can_be_created(): void
    {
        $this->post('/employees', [
            'first_name' => 'Ahmet',
            'last_name' => 'Yılmaz',
            'email' => 'Ahmet.Yilmaz@Sirket.test',
            'department' => 'Üretim',
        ])->assertRedirect('/employees');

        $this->assertDatabaseHas('employees', ['email' => 'ahmet.yilmaz@sirket.test', 'department' => 'Üretim']);
    }

    public function test_employee_validation(): void
    {
        $existing = Employee::factory()->create();

        $this->post('/employees', [
            'first_name' => '',
            'last_name' => 'Yılmaz',
            'email' => $existing->email,
            'department' => 'Olmayan Departman',
        ])->assertSessionHasErrors(['first_name', 'email', 'department']);
    }

    public function test_employee_can_be_updated(): void
    {
        $employee = Employee::factory()->create();

        $this->put("/employees/{$employee->id}", [
            'first_name' => 'Yeni',
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'department' => 'Kalite',
        ])->assertRedirect('/employees');

        $this->assertSame('Yeni', $employee->fresh()->first_name);
    }

    public function test_employee_can_be_deleted(): void
    {
        $employee = Employee::factory()->create();

        $this->delete("/employees/{$employee->id}")->assertRedirect('/employees');

        $this->assertSoftDeleted($employee);
    }

    public function test_deleted_employee_is_restored_when_added_again(): void
    {
        $employee = Employee::factory()->create(['email' => 'ahmet.yilmaz@sirket.test', 'department' => 'Üretim']);
        $employee->delete();

        $this->post('/employees', [
            'first_name' => 'Ahmet',
            'last_name' => 'Yılmaz',
            'email' => 'ahmet.yilmaz@sirket.test',
            'department' => 'Kalite',
        ])->assertRedirect('/employees')->assertSessionHasNoErrors();

        $this->assertSame(1, Employee::withTrashed()->count());
        $this->assertNotSoftDeleted($employee);
        $this->assertSame('Kalite', $employee->fresh()->department);
    }

    public function test_email_of_deleted_employee_cannot_be_taken_by_update(): void
    {
        Employee::factory()->create(['email' => 'eski@sirket.test'])->delete();
        $employee = Employee::factory()->create();

        $this->put("/employees/{$employee->id}", [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => 'eski@sirket.test',
            'department' => $employee->department,
        ])->assertSessionHasErrors('email');
    }

    public function test_search_is_case_insensitive_for_turkish_letters(): void
    {
        Employee::factory()->create(['first_name' => 'Fatma', 'last_name' => 'Çelik']);
        Employee::factory()->create(['first_name' => 'İrem', 'last_name' => 'Erdoğan']);
        Employee::factory()->create(['first_name' => 'Mustafa', 'last_name' => 'Şahin']);

        $this->get('/employees?search=çelik')->assertSee('Fatma Çelik')->assertDontSee('Mustafa Şahin');
        $this->get('/employees?search=irem')->assertSee('İrem Erdoğan');
        $this->get('/employees?search=mustafa şahin')->assertSee('Mustafa Şahin')->assertDontSee('Fatma Çelik');
    }

    public function test_employees_are_sorted_in_turkish_alphabetical_order(): void
    {
        foreach (['Zeynep', 'İrem', 'Hakan', 'Çağla', 'Can', 'Ömer', 'Onur'] as $name) {
            Employee::factory()->create(['first_name' => $name, 'last_name' => 'Test']);
        }

        $this->get('/employees')->assertSeeInOrder(['Can Test', 'Çağla Test', 'Hakan Test', 'İrem Test', 'Onur Test', 'Ömer Test', 'Zeynep Test']);
    }
}
