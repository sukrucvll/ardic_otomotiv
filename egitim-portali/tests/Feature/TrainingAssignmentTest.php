<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Models\User;
use App\Services\Personnel\PersonnelApiException;
use Database\Seeders\DemoAssignmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrainingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.personnel.base_url' => 'http://personel.test/api/v1',
            'services.personnel.token' => '1|test-token',
        ]);

        $this->actingAs(User::factory()->create());
    }

    private function fakePersonnelApi(): void
    {
        Http::fake([
            'personel.test/*' => Http::response([
                'data' => [
                    ['id' => 1, 'first_name' => 'Ahmet', 'last_name' => 'Yılmaz', 'email' => 'ahmet@sirket.test', 'department' => 'Üretim'],
                    ['id' => 2, 'first_name' => 'Ayşe', 'last_name' => 'Kaya', 'email' => 'ayse@sirket.test', 'department' => 'Kalite'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);
    }

    public function test_training_can_be_created(): void
    {
        $this->post('/trainings', [
            'title' => 'Temel İSG',
            'category' => 'İş Sağlığı ve Güvenliği',
            'duration_hours' => 8,
            'is_mandatory' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('trainings', ['title' => 'Temel İSG', 'is_mandatory' => true]);
    }

    public function test_employees_from_api_can_be_assigned_with_snapshot(): void
    {
        $this->fakePersonnelApi();
        $training = Training::factory()->create();

        $this->post("/trainings/{$training->id}/assignments", [
            'employee_ids' => [1, 2],
            'due_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect("/trainings/{$training->id}")->assertSessionHas('success');

        $this->assertDatabaseHas('training_assignments', [
            'training_id' => $training->id,
            'employee_id' => 2,
            'employee_name' => 'Ayşe Kaya',
            'employee_department' => 'Kalite',
            'status' => 'assigned',
        ]);
    }

    public function test_unknown_employee_ids_are_rejected(): void
    {
        $this->fakePersonnelApi();
        $training = Training::factory()->create();

        $this->post("/trainings/{$training->id}/assignments", ['employee_ids' => [1, 999]])
            ->assertSessionHasErrors('employee_ids');

        $this->assertDatabaseCount('training_assignments', 0);
    }

    public function test_duplicate_assignment_is_skipped(): void
    {
        $this->fakePersonnelApi();
        $training = Training::factory()->create();

        $this->post("/trainings/{$training->id}/assignments", ['employee_ids' => [1]]);
        $this->post("/trainings/{$training->id}/assignments", ['employee_ids' => [1, 2]]);

        $this->assertDatabaseCount('training_assignments', 2);
    }

    public function test_trainings_can_be_assigned_from_employee_page(): void
    {
        $this->fakePersonnelApi();
        $trainings = Training::factory()->count(2)->create();

        $this->post('/employees/1/assignments', ['training_ids' => $trainings->pluck('id')->all()])
            ->assertRedirect('/employees/1');

        $this->assertSame(2, TrainingAssignment::where('employee_id', 1)->count());
    }

    public function test_completing_assignment_records_completion_time(): void
    {
        $assignment = $this->makeAssignment();

        $this->patch("/assignments/{$assignment->id}", ['status' => 'completed'])->assertRedirect();

        $assignment->refresh();
        $this->assertSame(AssignmentStatus::Completed, $assignment->status);
        $this->assertNotNull($assignment->completed_at);

        $this->patch("/assignments/{$assignment->id}", ['status' => 'in_progress']);
        $this->assertNull($assignment->fresh()->completed_at);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $assignment = $this->makeAssignment();

        $this->patch("/assignments/{$assignment->id}", ['status' => 'bitti'])->assertSessionHasErrors('status');
    }

    public function test_overdue_assignments_are_detected(): void
    {
        $overdue = $this->makeAssignment(['due_date' => now()->subDay()]);
        $this->makeAssignment(['due_date' => now()->subDay(), 'status' => 'completed']);
        $this->makeAssignment(['due_date' => now()->addDay()]);

        $this->assertTrue($overdue->is_overdue);
        $this->assertSame([$overdue->id], TrainingAssignment::overdue()->pluck('id')->all());
    }

    public function test_pages_still_render_when_personnel_api_is_down(): void
    {
        Http::fake(['personel.test/*' => Http::response(null, 500)]);
        $assignment = $this->makeAssignment();

        $this->get("/trainings/{$assignment->training_id}")
            ->assertOk()
            ->assertSee($assignment->employee_name)
            ->assertSee('Personel bilgileri şu anda alınamıyor')
            ->assertDontSee('HTTP 500');

        $this->get('/')->assertOk();
        $this->get('/employees')->assertOk();
        $this->get("/employees/{$assignment->employee_id}")->assertOk()->assertSee($assignment->employee_name);
    }

    public function test_assignment_shows_friendly_error_when_api_is_down(): void
    {
        Http::fake(['personel.test/*' => Http::response(null, 401)]);
        $training = Training::factory()->create();

        $this->post("/trainings/{$training->id}/assignments", ['employee_ids' => [1]])
            ->assertSessionHas('error', PersonnelApiException::USER_MESSAGE);
    }

    public function test_all_pages_render_with_api_available(): void
    {
        $this->fakePersonnelApi();
        $assignment = $this->makeAssignment(['employee_id' => 1]);

        $this->get('/')->assertOk()->assertSee('Departman Bazında Tamamlanma');
        $this->get('/trainings')->assertOk();
        $this->get("/trainings/{$assignment->training_id}")->assertOk()->assertSee('Ayşe Kaya');
        $this->get('/assignments')->assertOk();
        $this->get('/assignments?status=overdue')->assertOk();
        $this->get('/employees?search=ayşe')->assertOk()->assertSee('Ayşe Kaya')->assertDontSee('Ahmet Yılmaz');
        $this->get('/employees/1')->assertOk()->assertSee('Ahmet Yılmaz');
    }

    public function test_pages_show_warning_when_api_returns_unexpected_body(): void
    {
        Http::fake(['personel.test/*' => Http::response('<html>Bakım</html>', 200, ['Content-Type' => 'text/html'])]);
        $training = Training::factory()->create();

        $this->get('/employees')->assertOk()->assertSee('şu anda alınamıyor');
        $this->get("/trainings/{$training->id}")->assertOk()->assertSee('şu anda alınamıyor');
    }

    public function test_assignment_rechecks_personnel_instead_of_using_cache(): void
    {
        $employee = ['id' => 1, 'first_name' => 'Ahmet', 'last_name' => 'Yılmaz', 'email' => 'ahmet@sirket.test', 'department' => 'Üretim'];
        Http::fakeSequence('personel.test/*')
            ->push(['data' => [$employee], 'meta' => ['last_page' => 1]])
            ->whenEmpty(Http::response(['data' => [], 'meta' => ['last_page' => 1]]));
        $training = Training::factory()->create();

        // Liste önbelleğe alınır, ardından personel Personel Sistemi'nden silinir
        $this->get('/employees')->assertOk()->assertSee('Ahmet Yılmaz');

        $this->post("/trainings/{$training->id}/assignments", ['employee_ids' => [1]])->assertSessionHasErrors('employee_ids');
        $this->post('/employees/1/assignments', ['training_ids' => [$training->id]])->assertNotFound();
        $this->assertSame(0, TrainingAssignment::count());
    }

    public function test_forms_still_render_after_malformed_input(): void
    {
        $this->fakePersonnelApi();
        $training = Training::factory()->create();

        $this->from('/trainings/create')->post('/trainings', ['title' => ['x'], 'category' => 'Teknik', 'duration_hours' => ['2'], 'description' => ['x']])
            ->assertRedirect('/trainings/create');
        $this->get('/trainings/create')->assertOk();

        $this->from("/trainings/{$training->id}")->post("/trainings/{$training->id}/assignments", ['employee_ids' => '1', 'due_date' => ['x']])
            ->assertRedirect("/trainings/{$training->id}");
        $this->get("/trainings/{$training->id}")->assertOk();

        $this->from('/employees/1')->post('/employees/1/assignments', ['training_ids' => '1', 'due_date' => ['x']])
            ->assertRedirect('/employees/1');
        $this->get('/employees/1')->assertOk();
    }

    public function test_all_pages_render_with_empty_database(): void
    {
        Http::fake(['personel.test/*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]])]);

        $this->get('/')->assertOk()->assertSee('Henüz atama yapılmadı.');
        $this->get('/trainings')->assertOk();
        $this->get('/trainings/create')->assertOk();
        $this->get('/assignments')->assertOk()->assertSee('Kriterlere uygun atama bulunamadı.');
        $this->get('/employees')->assertOk()->assertSee('Kriterlere uygun personel bulunamadı.');

        $training = Training::factory()->create();
        $this->get("/trainings/{$training->id}")->assertOk()->assertSee('kayıtlı personel yok.');
    }

    public function test_interface_does_not_expose_api_details(): void
    {
        $this->fakePersonnelApi();
        $assignment = $this->makeAssignment(['employee_id' => 1]);

        foreach (['/', '/trainings', "/trainings/{$assignment->training_id}", '/assignments', '/employees', '/employees/1'] as $url) {
            // Görünen metin kontrol edilir (formlardaki gizli CSRF _token alanı hariç)
            $this->get($url)->assertOk()->assertDontSeeText('API')->assertDontSeeText('token');
        }
    }

    public function test_assignment_search_is_case_insensitive_for_turkish_letters(): void
    {
        $this->makeAssignment(['employee_id' => 5, 'employee_name' => 'Fatma Çelik', 'employee_email' => 'fatma@sirket.test']);
        $this->makeAssignment(['employee_id' => 6, 'employee_name' => 'Mustafa Şahin', 'employee_email' => 'mustafa@sirket.test']);

        $this->get('/assignments?search=çelik')->assertOk()->assertSee('Fatma Çelik')->assertDontSee('Mustafa Şahin');
        $this->get('/assignments?search=ŞAHİN')->assertOk()->assertSee('Mustafa Şahin')->assertDontSee('Fatma Çelik');
    }

    public function test_employees_are_sorted_in_turkish_alphabetical_order(): void
    {
        Http::fake([
            'personel.test/*' => Http::response([
                'data' => collect(['Zeynep', 'İrem', 'Hakan', 'Çağla', 'Can'])->map(fn ($name, $i) => [
                    'id' => $i + 1, 'first_name' => $name, 'last_name' => 'Test', 'email' => "p{$i}@sirket.test", 'department' => 'Üretim',
                ])->all(),
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get('/employees')->assertOk()->assertSeeInOrder(['Can Test', 'Çağla Test', 'Hakan Test', 'İrem Test', 'Zeynep Test']);
    }

    public function test_demo_seeder_keeps_existing_assignment_statuses(): void
    {
        $this->fakePersonnelApi();
        $existing = $this->makeAssignment(['employee_id' => 1, 'status' => AssignmentStatus::InProgress]);

        $this->seed(DemoAssignmentSeeder::class);

        $this->assertSame(AssignmentStatus::InProgress, $existing->fresh()->status);
        $this->assertGreaterThan(1, TrainingAssignment::count());
    }

    public function test_employee_page_refreshes_snapshot(): void
    {
        $this->fakePersonnelApi();
        $assignment = $this->makeAssignment(['employee_id' => 2, 'employee_name' => 'Eski İsim']);

        $this->get('/employees/2')->assertOk();

        $this->assertSame('Ayşe Kaya', $assignment->fresh()->employee_name);
    }

    private function makeAssignment(array $attributes = []): TrainingAssignment
    {
        return TrainingAssignment::create(array_merge([
            'training_id' => Training::factory()->create()->id,
            'employee_id' => random_int(100, 999),
            'employee_name' => 'Mehmet Demir',
            'employee_email' => 'mehmet@sirket.test',
            'employee_department' => 'Üretim',
        ], $attributes));
    }
}
