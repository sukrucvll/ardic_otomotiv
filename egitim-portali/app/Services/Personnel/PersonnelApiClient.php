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

    // Hatalı bir last_page değeri sonsuz istek döngüsü oluşturmasın (100 × 100 = 10.000 personel)
    private const MAX_PAGES = 100;

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
    public function employees(bool $fresh = false): Collection
    {
        // Atama gibi veri yazan işlemler önbelleği atlayıp güncel listeyi ister
        if ($fresh) {
            Cache::forget(self::EMPLOYEES_CACHE_KEY);
        }

        // Önbellekte nesne değil düz dizi tutulur (Laravel önbellekten sınıf açmaz)
        $rows = Cache::remember(self::EMPLOYEES_CACHE_KEY, $this->cacheTtl, fn () => $this->fetchAllEmployees());

        return collect($rows)
            ->map(fn (array $row) => EmployeeData::fromArray($row))
            ->keyBy('id');
    }

    /**
     * @throws PersonnelApiException
     */
    public function find(int $id, bool $fresh = false): ?EmployeeData
    {
        return $this->employees($fresh)->get($id);
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

            // Yanıt önbelleğe yazılmadan önce doğrulanır; bozuk veri sayfaları çökertmesin
            if (! is_array($json['data'] ?? null) || ! array_is_list($json['data'])) {
                $this->fail(PersonnelApiException::invalidResponse(), 'employees');
            }

            foreach ($json['data'] as $row) {
                if (! $this->isValidEmployee($row)) {
                    $this->fail(PersonnelApiException::invalidResponse(), 'employees');
                }

                $rows[] = $row;
            }

            $lastPage = min((int) ($json['meta']['last_page'] ?? 1), self::MAX_PAGES);
        } while (++$page <= $lastPage);

        return $rows;
    }

    private function isValidEmployee(mixed $row): bool
    {
        return is_array($row)
            && filter_var($row['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false
            && collect(['first_name', 'last_name', 'email', 'department'])->every(fn ($field) => is_string($row[$field] ?? null));
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

        // 200 dönse bile gövde JSON olmayabilir (ör. yanlış adres, araya giren bir HTML sayfası)
        $json = $response->json();

        if (! is_array($json)) {
            $this->fail(PersonnelApiException::invalidResponse(), $path);
        }

        return $json;
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
