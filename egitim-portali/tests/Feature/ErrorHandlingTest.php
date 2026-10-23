<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
            ->get('/trainings/999')
            ->assertNotFound()
            ->assertSee('Sayfa bulunamadı')
            ->assertSee('Ana sayfaya dön');
    }

    public function test_malformed_filters_do_not_cause_server_error(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            '/employees?search[]=x', '/employees?department[]=x',
            '/trainings?search[]=x', '/trainings?category[]=x',
            '/assignments?status[]=x', '/assignments?training[]=1', '/assignments?department[]=x', '/assignments?search[]=x',
        ] as $url) {
            $this->get($url)->assertRedirect();
        }
    }

    public function test_malformed_login_input_is_rejected_and_login_page_still_renders(): void
    {
        $this->from('/login')->post('/login', ['email' => ['x'], 'password' => 'y'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->get('/login')->assertOk();
    }

    public function test_out_of_range_employee_id_is_not_found(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees/99999999999999999999')->assertNotFound();
        $this->post('/employees/99999999999999999999/assignments', ['training_ids' => [1]])->assertNotFound();
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
            ->from('/trainings')
            ->post('/_test/expired')
            ->assertRedirect('/trainings')
            ->assertSessionHas('error');
    }

    public function test_remember_cookie_name_does_not_clash_with_personnel_system(): void
    {
        // Personel Sistemi 'web' guard'ını kullanır; portalın çerez adı farklı olmalı
        $this->assertStringStartsWith('remember_portal_', Auth::guard()->getRecallerName());
    }
}
