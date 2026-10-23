<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class IssueApiToken extends Command
{
    protected $signature = 'personnel:issue-token
                            {client=Eğitim Portalı : API istemcisinin adı}
                            {--days= : Token geçerlilik süresi (gün). Boş bırakılırsa süresiz}
                            {--revoke : Yeni token üretmeden istemcinin tüm token\'larını iptal eder}';

    protected $description = 'Bir API istemcisi için salt okunur servis token\'ı üretir (eski token\'lar iptal edilir)';

    public function handle(): int
    {
        if ($this->option('revoke')) {
            return $this->revoke();
        }

        $client = ApiClient::firstOrCreate(
            ['name' => $this->argument('client')],
            ['description' => 'Personel verilerini okuyan servis'],
        );

        // Token rotasyonu: istemcinin aynı anda tek geçerli token'ı olur
        $client->tokens()->delete();

        $days = $this->option('days');
        $token = $client->createToken(
            'service-token',
            config('personnel.api.abilities'),
            $days ? now()->addDays((int) $days) : null,
        );

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
