<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    /**
     * Demo için örnek personel kayıtları.
     */
    public function run(): void
    {
        $employees = [
            ['Ahmet', 'Yılmaz', 'Üretim'],
            ['Ayşe', 'Kaya', 'İnsan Kaynakları'],
            ['Mehmet', 'Demir', 'Üretim'],
            ['Fatma', 'Çelik', 'Kalite'],
            ['Mustafa', 'Şahin', 'Lojistik'],
            ['Zeynep', 'Yıldız', 'Ar-Ge'],
            ['Emre', 'Aydın', 'Bilgi Teknolojileri'],
            ['Elif', 'Öztürk', 'Finans'],
            ['Burak', 'Arslan', 'Satış ve Pazarlama'],
            ['Selin', 'Doğan', 'Satış Sonrası Hizmetler'],
            ['Can', 'Kılıç', 'Üretim'],
            ['Merve', 'Aslan', 'Kalite'],
            ['Hakan', 'Çetin', 'Satın Alma'],
            ['Gizem', 'Kara', 'Ar-Ge'],
            ['Oğuz', 'Koç', 'Bilgi Teknolojileri'],
            ['Deniz', 'Kurt', 'Lojistik'],
            ['Serkan', 'Özdemir', 'Üretim'],
            ['Büşra', 'Şimşek', 'İnsan Kaynakları'],
            ['Kerem', 'Polat', 'Satış ve Pazarlama'],
            ['İrem', 'Erdoğan', 'Finans'],
            ['Onur', 'Güneş', 'Satış Sonrası Hizmetler'],
            ['Ece', 'Aksoy', 'Kalite'],
            ['Tolga', 'Bulut', 'Üretim'],
            ['Seda', 'Tekin', 'Satın Alma'],
        ];

        // Tekrar çalıştırılabilir: var olan (silinmiş dahil) kayıtlar yeniden eklenmez
        foreach ($employees as [$firstName, $lastName, $department]) {
            Employee::withTrashed()->firstOrCreate(
                ['email' => Str::lower(Str::ascii("{$firstName}.{$lastName}")).'@sirket.test'],
                ['first_name' => $firstName, 'last_name' => $lastName, 'department' => $department],
            );
        }
    }
}
