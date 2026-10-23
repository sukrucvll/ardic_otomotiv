<?php

namespace App\Services\Personnel;

use App\Support\TurkishText;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Personel Sistemi REST API istemcisi.
 *
 * Portal personel verisini kendi veritabanına kopyalamaz; her zaman bu
 * istemci üzerinden okur. Her sayfa açılışında API'ye gitmemek için
 * yanıtlar kısa süreli önbelleğe alınır.
 */
class PersonnelApiClient
{
    private const EMPLOYEES_CACHE_KEY = 'personnel.employees';

    private const PER_PAGE = 100;

    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $token,
        private readonly int $timeout = 5,
        private readonly int $cacheTtl = 60,
    ) {}

    /**
     * Tüm personel, ID'ye göre anahtarlanmış.
     *
     * @return Collection<int, EmployeeData>
     *
     * @throws PersonnelApiException
     */
    public function employees(): Collection
    {
        // Önbellekte nesne değil düz dizi tutulur (Laravel önbellekten sınıf açmaz)
        $rows = Cache::remember(self::EMPLOYEES_CACHE_KEY, $this->cacheTtl, fn () => $this->fetchAllEmployees());

        return collect($rows)
            ->map(fn (array $row) => EmployeeData::fromArray($row))
            ->keyBy('id');
    }

    /**
     * @throws PersonnelApiException
     */
    public function find(int $id): ?EmployeeData
    {
        return $this->employees()->get($id);
    }

    /**
     * Personel listesindeki departmanlar (alfabetik).
     *
     * @return Collection<int, string>
     *
     * @throws PersonnelApiException
     */
    public function departments(): Collection
    {
        return $this->employees()->pluck('department')->unique()->sort(TurkishText::compare(...))->values();
    }

    /**
     * Sayfalı API yanıtını sonuna kadar dolaşır.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllEmployees(): array
    {
        $rows = [];
        $page = 1;

        do {
            $json = $this->get('employees', ['per_page' => self::PER_PAGE, 'page' => $page]);
            array_push($rows, ...$json['data']);
            $lastPage = (int) ($json['meta']['last_page'] ?? 1);
        } while (++$page <= $lastPage);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        if (blank($this->token)) {
            $this->fail(PersonnelApiException::missingToken(), $path);
        }

        try {
            $response = $this->request()->get($path, $query);
        } catch (ConnectionException $e) {
            $this->fail(PersonnelApiException::unreachable($e), $path);
        }

        if ($response->failed()) {
            $this->fail(PersonnelApiException::fromResponse($response), $path);
        }

        return $response->json();
    }

    /**
     * Teknik ayrıntıyı log'a yazar; arayüz yalnızca genel bir mesaj gösterir.
     */
    private function fail(PersonnelApiException $e, string $path): never
    {
        Log::warning('Personel API isteği başarısız', [
            'path' => $path,
            'status' => $e->getCode() ?: null,
            'error' => $e->getMessage(),
        ]);

        throw $e;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($this->token)
            ->withUserAgent('EgitimPortali/1.0')
            ->timeout($this->timeout)
            // Bağlantı hatası ve 5xx yanıtlarında 2 kez daha dene; 4xx'te denemenin anlamı yok
            ->retry(3, 200, function (Throwable $e) {
                return $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response->serverError());
            }, throw: false);
    }
}
