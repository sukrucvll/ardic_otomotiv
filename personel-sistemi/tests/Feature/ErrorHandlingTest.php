<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Testlerde CSRF doğrulaması devre dışı olduğu için 419 durumu elle üretilir
        Route::middleware('web')->post('/_test/expired', fn () => abort(419));
    }

    public function test_missing_record_shows_turkish_404_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/employees/999/edit')
            ->assertNotFound()
            ->assertSee('Sayfa bulunamadı')
            ->assertSee('Ana sayfaya dön');
    }

    public function test_malformed_filters_do_not_cause_server_error(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees?search[]=x')->assertRedirect();
        $this->get('/employees?department[]=x')->assertRedirect();
    }

    public function test_malformed_employee_fields_are_rejected_and_form_still_renders(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['first_name', 'last_name', 'email'] as $field) {
            $this->from('/employees/create')
                ->post('/employees', array_merge([
                    'first_name' => 'Ahmet', 'last_name' => 'Yılmaz', 'email' => 'ahmet@sirket.test', 'department' => 'Üretim',
                ], [$field => ['x']]))
                ->assertRedirect('/employees/create')
                ->assertSessionHasErrors($field);

            $this->get('/employees/create')->assertOk();
        }
    }

    public function test_malformed_login_input_is_rejected_and_login_page_still_renders(): void
    {
        $this->from('/login')->post('/login', ['email' => ['x'], 'password' => 'y'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->get('/login')->assertOk();
    }

    public function test_api_errors_stay_json(): void
    {
        $this->getJson('/api/v1/employees/999')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_expired_page_redirects_guest_to_login(): void
    {
        $this->post('/_test/expired')
            ->assertRedirect('/login')
            ->assertSessionHas('error');
    }

    public function test_expired_page_sends_logged_in_user_back(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/employees')
            ->post('/_test/expired')
            ->assertRedirect('/employees')
            ->assertSessionHas('error');
    }
}
