<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IssueApiToken extends Command
{
    protected $signature = 'personnel:issue-token
                            {client=Eğitim Portalı : API istemcisinin adı}
                            {--days= : Token geçerlilik süresi (1-3650 gün). Verilmezse süresiz}
                            {--revoke : Yeni token üretmeden istemcinin tüm token\'larını iptal eder}';

    protected $description = 'Bir API istemcisi için salt okunur servis token\'ı üretir (eski token\'lar iptal edilir)';

    private const MAX_DAYS = 3650;

    public function handle(): int
    {
        if ($this->option('revoke')) {
            return $this->revoke();
        }

        // Hatalı süre, mevcut token'a dokunulmadan reddedilir
        $days = $this->option('days');

        if ($days !== null) {
            $days = filter_var($days, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::MAX_DAYS]]);

            if ($days === false) {
                $this->components->error('--days 1 ile '.self::MAX_DAYS.' arasında bir tam sayı olmalı. Süresiz token için seçeneği kullanmayın.');

                return self::FAILURE;
            }
        }

        // Token rotasyonu: istemcinin aynı anda tek geçerli token'ı olur. Silme ve
        // oluşturma tek işlemde yapılır; yeni token oluşturulamazsa eskisi geçerli kalır.
        [$client, $token] = DB::transaction(function () use ($days) {
            $client = ApiClient::firstOrCreate(
                ['name' => $this->argument('client')],
                ['description' => 'Personel verilerini okuyan servis'],
            );

            $client->tokens()->delete();

            return [$client, $client->createToken(
                'service-token',
                config('personnel.api.abilities'),
                $days !== null ? now()->addDays($days) : null,
            )];
        });

        $this->components->info("{$client->name} için token üretildi. Bu değer bir daha gösterilmeyecek:");
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->line('Eğitim portalının .env dosyasına PERSONNEL_API_TOKEN olarak ekleyin.');

        return self::SUCCESS;
    }

    private function revoke(): int
    {
        $client = ApiClient::where('name', $this->argument('client'))->first();

        if (! $client) {
            $this->components->error("{$this->argument('client')} adında bir API istemcisi yok.");

            return self::FAILURE;
        }

        $count = $client->tokens()->delete();
        $this->components->info("{$client->name} istemcisinin {$count} token'ı iptal edildi. API erişimi kapandı.");

        return self::SUCCESS;
    }
}
