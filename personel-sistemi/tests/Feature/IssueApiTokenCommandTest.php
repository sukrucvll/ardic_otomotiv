<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;
use Tests\TestCase;

class IssueApiTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_issues_read_only_token(): void
    {
        $this->artisan('personnel:issue-token', ['--days' => 90])->assertSuccessful();

        $token = ApiClient::firstWhere('name', 'Eğitim Portalı')->tokens()->sole();
        $this->assertSame(['employees:read'], $token->abilities);
        $this->assertTrue($token->expires_at->isFuture());
    }

    public function test_reissuing_token_invalidates_old_one(): void
    {
        $client = ApiClient::create(['name' => 'Eğitim Portalı']);
        $old = $client->createToken('service-token', ['employees:read'])->plainTextToken;

        $this->artisan('personnel:issue-token')->assertSuccessful();

        $this->withToken($old)->getJson('/api/v1/employees')->assertUnauthorized();
        $this->assertSame(1, $client->tokens()->count());
    }

    public function test_revoke_option_removes_all_tokens(): void
    {
        $client = ApiClient::create(['name' => 'Eğitim Portalı']);
        $token = $client->createToken('service-token', ['employees:read'])->plainTextToken;

        $this->artisan('personnel:issue-token', ['--revoke' => true])->assertSuccessful();

        $this->assertSame(0, $client->tokens()->count());
        $this->withToken($token)->getJson('/api/v1/employees')->assertUnauthorized();
    }

    public function test_invalid_days_option_is_rejected_without_touching_existing_token(): void
    {
        $client = ApiClient::create(['name' => 'Eğitim Portalı']);
        $token = $client->createToken('service-token', ['employees:read'])->plainTextToken;

        foreach (['abc', '-1', '0', '1.5', '99999'] as $days) {
            $this->artisan('personnel:issue-token', ['--days' => $days])->assertFailed();
        }

        $this->assertSame(1, $client->tokens()->count());
        $this->withToken($token)->getJson('/api/v1/employees')->assertOk();
    }

    public function test_failed_reissue_keeps_existing_token(): void
    {
        $client = ApiClient::create(['name' => 'Eğitim Portalı']);
        $token = $client->createToken('service-token', ['employees:read'])->plainTextToken;

        PersonalAccessToken::creating(fn () => throw new RuntimeException('Kayıt hatası'));

        try {
            $this->artisan('personnel:issue-token')->run();
            $this->fail('Komut hata vermeliydi.');
        } catch (RuntimeException $e) {
            $this->assertSame('Kayıt hatası', $e->getMessage());
        }

        $this->assertSame(1, $client->tokens()->count());
        $this->withToken($token)->getJson('/api/v1/employees')->assertOk();
    }

    public function test_revoke_fails_for_unknown_client(): void
    {
        $this->artisan('personnel:issue-token', ['client' => 'Olmayan', '--revoke' => true])->assertFailed();
    }
}
