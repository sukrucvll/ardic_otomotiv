<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/employees')->assertRedirect('/login');
        $this->post('/employees', [])->assertRedirect('/login');
    }

    public function test_login_page_is_displayed(): void
    {
        $this->get('/login')->assertOk()->assertSee('Giriş yap');
    }

    public function test_seeded_admin_can_log_in(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post('/login', [
            'email' => config('personnel.admin.email'),
            'password' => config('personnel.admin.password'),
        ])->assertRedirect('/employees');

        $this->assertAuthenticated();
    }

    public function test_admin_password_is_stored_hashed(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->assertNotSame(config('personnel.admin.password'), User::first()->password);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'yanlis-parola'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_locked_after_too_many_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $user->email, 'password' => 'yanlis-parola']);
        }

        // 6. denemede doğru parola bile kabul edilmez
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/employees');
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/employees')->assertSee('btn-logout', false);
        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_api_does_not_accept_web_session(): void
    {
        // Panel oturumu API'ye erişim sağlamaz; API yalnızca servis token'ı kabul eder
        $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/employees')
            ->assertUnauthorized();
    }
}
