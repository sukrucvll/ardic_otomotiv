<?php

namespace Database\Seeders;

use App\Models\Training;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    /**
     * Demo için örnek eğitimler.
     */
    public function run(): void
    {
        $trainings = [
            ['Temel İş Sağlığı ve Güvenliği', 'İş Sağlığı ve Güvenliği', 8, true,
                '6331 sayılı kanun kapsamında tüm çalışanlar için zorunlu temel İSG eğitimi: risk kavramı, kişisel koruyucu donanım, acil durum ve tahliye.'],
            ['Yangın Güvenliği ve Tahliye', 'İş Sağlığı ve Güvenliği', 4, true,
                'Yangın sınıfları, söndürücü kullanımı ve tahliye planı uygulaması.'],
            ['IATF 16949 Kalite Yönetim Sistemi', 'Kalite', 12, false,
                'Otomotiv sektörü kalite yönetim sistemi gereklilikleri, süreç yaklaşımı ve iç denetim temelleri.'],
            ['8D Problem Çözme Tekniği', 'Kalite', 6, false,
                'Müşteri şikayetlerine yönelik 8 disiplinli problem çözme metodolojisi ve kök neden analizi.'],
            ['Yalın Üretim ve 5S', 'Teknik', 6, false,
                'İsrafın tanımlanması, 5S uygulamaları ve sürekli iyileştirme (Kaizen) kültürü.'],
            ['KVKK ve Bilgi Güvenliği Farkındalığı', 'Uyum ve Mevzuat', 2, true,
                'Kişisel verilerin korunması, oltalama saldırıları ve güvenli parola kullanımı.'],
            ['Etkili İletişim ve Ekip Çalışması', 'Kişisel Gelişim', 4, false,
                'Geri bildirim verme, aktif dinleme ve ekip içi iş birliği.'],
            ['Excel ile Veri Analizi', 'Yazılım ve Dijital', 8, false,
                'Pivot tablolar, temel formüller ve raporlama.'],
        ];

        // Tekrar çalıştırılabilir: aynı adlı eğitim yeniden eklenmez
        foreach ($trainings as [$title, $category, $hours, $mandatory, $description]) {
            Training::firstOrCreate(
                ['title' => $title],
                ['category' => $category, 'duration_hours' => $hours, 'is_mandatory' => $mandatory, 'description' => $description],
            );
        }
    }
}
