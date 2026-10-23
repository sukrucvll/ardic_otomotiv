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
        foreach (['/', '/trainings', '/trainings/create', '/assignments', '/employees', '/employees/1'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        $this->post('/trainings', [])->assertRedirect('/login');
    }

    public function test_login_page_is_displayed(): void
    {
        $this->get('/login')->assertOk()->assertSee('Giriş yap');
    }

    public function test_seeded_admin_can_log_in(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post('/login', [
            'email' => config('training.admin.email'),
            'password' => config('training.admin.password'),
        ])->assertRedirect('/');

        $this->assertAuthenticated();
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

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/');
    }

    public function test_user_can_log_out_from_sidebar(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/assignments')->assertSee('Çıkış yap');
        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
