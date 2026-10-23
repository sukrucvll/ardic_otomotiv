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
