<?php

namespace Tests\Feature;

use App\Services\Personnel\PersonnelApiClient;
use App\Services\Personnel\PersonnelApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PersonnelApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.personnel.base_url' => 'http://personel.test/api/v1',
            'services.personnel.token' => '1|test-token',
        ]);
    }

    private function client(): PersonnelApiClient
    {
        return $this->app->make(PersonnelApiClient::class);
    }

    public function test_it_sends_bearer_token_and_maps_employees(): void
    {
        Http::fake([
            'personel.test/*' => Http::response([
                'data' => [['id' => 7, 'first_name' => 'Ayşe', 'last_name' => 'Kaya', 'email' => 'ayse@sirket.test', 'department' => 'Kalite']],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $employees = $this->client()->employees();

        $this->assertSame('Ayşe Kaya', $employees->get(7)->fullName());

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer 1|test-token')
            && $request->hasHeader('Accept', 'application/json')
            && str_starts_with($request->url(), 'http://personel.test/api/v1/employees'));
    }

    public function test_it_follows_all_pages(): void
    {
        Http::fake([
            'personel.test/api/v1/employees?per_page=100&page=1' => Http::response([
                'data' => [['id' => 1, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@x.test', 'department' => 'Üretim']],
                'meta' => ['last_page' => 2],
            ]),
            'personel.test/api/v1/employees?per_page=100&page=2' => Http::response([
                'data' => [['id' => 2, 'first_name' => 'C', 'last_name' => 'D', 'email' => 'c@x.test', 'department' => 'Kalite']],
                'meta' => ['last_page' => 2],
            ]),
        ]);

        $this->assertSame([1, 2], $this->client()->employees()->keys()->all());
        Http::assertSentCount(2);
    }

    public function test_responses_are_cached(): void
    {
        Http::fake(['personel.test/*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]])]);

        $this->client()->employees();
        $this->client()->employees();

        Http::assertSentCount(1);
    }

    public function test_unexpected_response_body_throws_exception(): void
    {
        $bodies = [
            'HTML' => Http::response('<html>Bakım</html>', 200, ['Content-Type' => 'text/html']),
            'data yok' => Http::response(['message' => 'ok']),
            'data dizi değil' => Http::response(['data' => 'x', 'meta' => ['last_page' => 1]]),
            'eksik alan' => Http::response(['data' => [['id' => 1]], 'meta' => ['last_page' => 1]]),
            'alan türü yanlış' => Http::response(['data' => [['id' => 1, 'first_name' => ['x'], 'last_name' => 'B', 'email' => 'a@x.test', 'department' => 'Üretim']], 'meta' => ['last_page' => 1]]),
        ];

        foreach ($bodies as $case => $response) {
            Http::fake(['personel.test/*' => $response]);
            Cache::flush();

            try {
                $this->client()->employees();
                $this->fail("Beklenmeyen yanıt kabul edildi: {$case}");
            } catch (PersonnelApiException) {
                $this->assertFalse(Cache::has('personnel.employees'), "Bozuk yanıt önbelleğe yazıldı: {$case}");
            }
        }
    }

    public function test_unauthorized_response_throws_meaningful_exception(): void
    {
        Http::fake(['personel.test/*' => Http::response(['message' => 'Unauthenticated.'], 401)]);

        $this->expectException(PersonnelApiException::class);
        $this->expectExceptionMessage('token');

        $this->client()->employees();
    }

    public function test_failures_are_logged_with_technical_details(): void
    {
        Http::fake(['personel.test/*' => Http::response(null, 403)]);
        Log::spy();

        try {
            $this->client()->employees();
        } catch (PersonnelApiException) {
        }

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message, $context) => $context['status'] === 403 && str_contains($context['error'], 'yetkisi'));
    }

    public function test_connection_failure_throws_exception(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->expectException(PersonnelApiException::class);
        $this->expectExceptionMessage('ulaşılamıyor');

        $this->client()->employees();
    }

    public function test_missing_token_does_not_call_api(): void
    {
        config(['services.personnel.token' => null]);
        Http::fake();

        try {
            $this->client()->employees();
            $this->fail('Exception bekleniyordu.');
        } catch (PersonnelApiException) {
            Http::assertNothingSent();
        }
    }
}
