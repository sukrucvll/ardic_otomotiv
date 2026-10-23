# Personel Sistemi ile Entegre Eğitim Portalı

Birbirinden bağımsız iki Laravel uygulaması:

| Uygulama | Görev | Port | Veritabanı |
|---|---|---|---|
| **personel-sistemi** | Personel (ad, soyad, e-posta, departman) yönetimi + REST API | `8001` | `personel-sistemi/database/database.sqlite` |
| **egitim-portali** | Eğitim tanımlama, personele atama, tamamlanma takibi | `8002` | `egitim-portali/database/database.sqlite` |

İki uygulama **aynı veritabanını kullanmaz**. Eğitim portalı personel bilgisine yalnızca Personel Sistemi'nin REST API'si üzerinden, servis token'ı ile erişir.

**Canlı demo:**

- Personel Sistemi: https://personel.turkuazcode.com
- Eğitim Portalı: https://egitim.turkuazcode.com

Canlı ortam kurulumu (nginx, HTTPS, `.env` ayarları) için: [deploy/README.md](deploy/README.md)

## Mimari

```
┌──────────────────────────────┐                          ┌──────────────────────────────┐
│      PERSONEL SİSTEMİ        │     GET /api/v1/...      │        EĞİTİM PORTALI        │
│      Laravel 13 · :8001      │◄─────────────────────────│      Laravel 13 · :8002      │
│                              │  Authorization: Bearer   │                              │
│ • Personel CRUD ekranı       │  (yalnızca okuma yetkisi)│ • Panel (istatistikler)      │
│ • Giriş (İK yöneticisi)      │                          │ • Eğitim CRUD                │
│ • /api/v1/employees          │                          │ • Personel atama             │
│ • /api/v1/employees/{id}     │                          │ • Durum takibi               │
│ • /api/v1/departments        │                          │ • PersonnelApiClient + cache │
└──────────────┬───────────────┘                          └──────────────┬───────────────┘
               │                                                         │
        ┌──────▼──────┐                                           ┌──────▼──────┐
        │  SQLite #1  │  employees, api_clients,                  │  SQLite #2  │  trainings,
        │             │  personal_access_tokens                   │             │  training_assignments
        └─────────────┘                                           └─────────────┘
```

**Teknolojiler:** PHP 8.3+ · Laravel 13 · Laravel Sanctum · SQLite · Blade · Bootstrap 5.3 (CDN) · Bootstrap Icons · PHPUnit

## Kurulum

Gereksinimler: **PHP 8.3+** (`pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `curl` eklentileriyle) ve **Composer**. Node veya npm gerekmez, Bootstrap CDN üzerinden yüklenir.

Her uygulamada `composer setup` tek komutla kurulumu yapar: bağımlılıkları yükler, `.env` dosyasını (yoksa) oluşturur, SQLite veritabanı dosyasını oluşturur, uygulama anahtarını üretir, tabloları kurar ve örnek verileri ekler. Komut tekrar çalıştırılabilir; mevcut kayıtlar çoğaltılmaz.

### 1) Personel Sistemi

```bash
cd personel-sistemi
composer setup                       # İK yöneticisi + 24 örnek personel eklenir
php artisan personnel:issue-token    # Eğitim Portalı için token üretir ve ekrana yazar
php artisan serve --port=8001
```

**Giriş bilgileri** (Personel Sistemi paneli):

| E-posta | Parola |
|---|---|
| `ik@admin.com` | `153624##AA` |

Bu bilgiler `.env` dosyasındaki `ADMIN_NAME`, `ADMIN_EMAIL` ve `ADMIN_PASSWORD` ile değiştirilebilir (`php artisan db:seed --class=AdminUserSeeder`).

Token yönetimi yalnızca backend'den, terminal komutuyla yapılır:

```bash
php artisan personnel:issue-token              # yeni token üretir, eskisini iptal eder (rotasyon)
php artisan personnel:issue-token --days=90    # 90 gün geçerli token
php artisan personnel:issue-token --revoke     # tüm token'ları iptal eder, portalın erişimi kapanır
```

### 2) Eğitim Portalı (yeni bir terminalde)

```bash
cd egitim-portali
cp .env.example .env                 # Windows (cmd): copy .env.example .env
```

`.env` dosyasına bir önceki adımda üretilen token'ı yazın:

```dotenv
PERSONNEL_API_URL=http://127.0.0.1:8001/api/v1
PERSONNEL_API_TOKEN=1|xxxxxxxxxxxxxxxxxxxxxxxx
```

```bash
composer setup                       # 8 örnek eğitim; Personel Sistemi çalışıyorsa demo atamalar da yapılır
php artisan serve --port=8002
```

> Token'ı `composer setup`'tan sonra eklediyseniz demo atamalar için: `php artisan db:seed --class=DemoAssignmentSeeder`

**Giriş bilgileri** (Eğitim Portalı):

| E-posta | Parola |
|---|---|
| `egitim@admin.com` | `153624##AA` |

Bu bilgiler portalın `.env` dosyasındaki `ADMIN_NAME`, `ADMIN_EMAIL` ve `ADMIN_PASSWORD` ile değiştirilebilir.

- Personel Sistemi: http://localhost:8001
- Eğitim Portalı: http://localhost:8002

İki uygulamanın kullanıcıları da birbirinden bağımsızdır (her biri kendi veritabanında). Çıkış için sol menünün altındaki **Çıkış yap** butonu veya sağ üstteki kullanıcı menüsü kullanılır.

### Testler

```bash
cd personel-sistemi && php artisan test   # 44 test: giriş/oturum, API güvenliği, Türkçe arama/sıralama, CRUD, token komutu, hata sayfaları
cd ../egitim-portali && php artisan test  # 42 test: giriş/oturum, API istemcisi, atama akışı, Türkçe arama/sıralama, API kapalıyken davranış, hata sayfaları
```

Portal testleri Personel API'sini `Http::fake()` ile taklit eder, çalışan bir Personel Sistemi gerektirmez.

## Sistemler Arası Güvenlik

Portal, Personel API'sine **Laravel Sanctum servis token'ı** ile bağlanır. Her istekte `Authorization: Bearer <token>` başlığı gönderilir.

| Kontrol | Nasıl uygulandı |
|---|---|
| **Kimlik doğrulama** | `auth:sanctum` middleware'i. Token yoksa veya geçersizse `401` döner. |
| **Hash'li saklama** | Token veritabanında yalnızca **SHA-256 hash** olarak tutulur. Düz metin token sadece üretildiği anda bir kez gösterilir. Veritabanı sızsa bile token'lar kullanılamaz. |
| **En az yetki** | Token sadece `employees:read` yetkisini (ability) taşır ve `abilities:employees:read` middleware'i bunu kontrol eder. API'de yazma ucu yoktur; token ele geçirilse bile veri değiştirilemez. Yetkisiz token `403` alır. |
| **Rate limit** | IP başına dakikada 60 istek (`PERSONNEL_API_RATE_LIMIT`). Token tahmin denemelerini yavaşlatır, aşımda `429` döner. |
| **Süre sonu / iptal / rotasyon** | `--days` ile süre verilebilir. Yeni token üretildiğinde eskisi anında geçersiz olur; `--revoke` ile erişim tamamen kapatılır. Son kullanım zamanı (`last_used_at`) kaydedilir. |
| **Sır yönetimi** | Token kodda değil, portalın `.env` dosyasında durur (`.env` git'e girmez). |
| **Veri sızıntısı kontrolü** | API çıktısı `EmployeeResource` ile açıkça tanımlanır, model doğrudan dışarı verilmez. Silinmiş (soft delete) personel API'de görünmez. |
| **Oturum ve token ayrımı** | Panel oturumu API'ye erişim sağlamaz (`sanctum.guard = []`). API sadece servis token'ı kabul eder; insan kullanıcı ile servis kimliği birbirinden ayrıdır. |

### Kullanıcı girişi (her iki uygulama)

Her iki uygulamanın ekranları da yalnızca oturum açmış kullanıcılara açıktır; ilk sayfa giriş ekranıdır.

- Tüm yönetim rotaları `auth` middleware'i arkasında; giriş yapmayan kullanıcı `/login` sayfasına yönlendirilir.
- Parolalar **bcrypt** ile hash'lenerek saklanır.
- Aynı e-posta ve IP için **5 hatalı denemeden sonra** giriş geçici olarak kilitlenir (kaba kuvvet koruması).
- Başarılı girişte oturum kimliği yenilenir (session fixation koruması), çıkışta oturum ve CSRF token'ı geçersiz kılınır.
- Tüm formlar CSRF korumalıdır.
- Demo hesap bilgileri giriş sayfasında yalnızca `APP_ENV=local` iken gösterilir.

**Neden Sanctum?** Laravel'in yerleşik çözümü olduğu için ek bağımlılık getirmiyor. Düz bir "API key" yaklaşımından farklı olarak hash'li saklama, yetki kısıtlama (ability), süre sonu ve iptal hazır geliyor. Bu ölçekte OAuth2 (Passport) gereksiz karmaşıklık olurdu.

**Canlı ortam için eklenmesi gerekenler:** API trafiği HTTPS üzerinden olmalı (Bearer token düz HTTP'de dinlenebilir). İhtiyaç olursa IP allowlist veya replay saldırılarına karşı HMAC imzalı istek (timestamp + imza) eklenebilir.

## Tasarım Kararları

- **Personel verisi portala kopyalanmaz.** Tek doğruluk kaynağı (single source of truth) Personel Sistemi'dir. Portal listeyi `PersonnelApiClient` ile API'den çeker, sayfalı yanıtı sonuna kadar dolaşır ve **1 dakika önbelleğe alır** (`PERSONNEL_API_CACHE_TTL`). Entegrasyon tamamen backend'de kalır; portal arayüzünde API adresi, token veya bağlantı ayrıntısı gösterilmez.
- **Atamalarda dış referans + snapshot.** `training_assignments.employee_id` Personel Sistemi'ndeki ID'dir. Farklı veritabanı olduğu için foreign key yoktur. Atama anında ad, e-posta ve departman da kaydedilir. Böylece:
  - Personel API'si çökse bile atamalar ve geçmiş görüntülenebilir. Kullanıcıya sade bir uyarı gösterilir ("Personel bilgileri şu anda alınamıyor"); teknik ayrıntı (HTTP kodu, token hatası) yalnızca `storage/logs/laravel.log` dosyasına yazılır,
  - Personel silinse bile "bu eğitimi kim almıştı" bilgisi kaybolmaz ("Sistemde yok" etiketiyle gösterilir).
  - Snapshot'lar, personel sayfası açıldığında güncel bilgiyle eşitlenir.
- **Formdan gelen ID'lere güvenilmez.** Atama yapılırken seçilen personel ID'leri sunucu tarafında API'den doğrulanır. Personel Sistemi'nde olmayan bir ID ile atama yapılamaz.
- **Dayanıklı HTTP istemcisi.** 5 saniye timeout, bağlantı hatası ve 5xx yanıtlarında 2 yeniden deneme, 401/403/429 gibi durumlar için anlaşılır Türkçe hata mesajları.
- **Silinen personel geri eklenebilir.** Personel kalıcı silinmez (soft delete). Silinmiş bir kişi aynı e-postayla tekrar eklenirse eski kaydı aynı ID ile geri yüklenir, böylece portaldaki atama geçmişi kendiliğinden geri bağlanır.
- **Türkçe arama ve sıralama.** SQLite'ın `LIKE` işlemi yalnızca ASCII harflerde büyük/küçük harf duyarsızdır ("çelik" ile "Çelik" eşleşmez). Bu yüzden SQLite bağlantısına Türkçe kurallarına uygun `tr_lower()` fonksiyonu ve `turkish` sıralama kuralı (collation) eklenir; aramalar büyük/küçük harf duyarsız çalışır, isimler Türkçe alfabe sırasıyla listelenir.
- **Durum modeli:** `Atandı → Devam Ediyor → Tamamlandı` (PHP enum). Tamamlandığında `completed_at` kaydedilir. Son tarihi geçen ve tamamlanmamış atamalar **Gecikmiş** olarak işaretlenir.
- **Frontend:** Blade + Bootstrap 5 (CDN). Build adımı gerektirmez. İki uygulama aynı tasarım dilini kullanır ama vurgu renkleri farklıdır (Personel: indigo, Portal: teal), böylece hangi sistemde olduğunuz bir bakışta anlaşılır.

## API Uçları (Personel Sistemi)

| Method | Uç | Açıklama |
|---|---|---|
| `GET` | `/api/v1/employees` | Sayfalı liste. Parametreler: `search`, `department`, `per_page` (1–100), `page` |
| `GET` | `/api/v1/employees/{id}` | Tek personel (silinmişse `404`) |
| `GET` | `/api/v1/departments` | Departmanlar ve personel sayıları |

```bash
curl "http://localhost:8001/api/v1/employees?per_page=5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

```json
{
  "data": [
    { "id": 1, "first_name": "Ahmet", "last_name": "Yılmaz", "full_name": "Ahmet Yılmaz",
      "email": "ahmet.yilmaz@sirket.test", "department": "Üretim",
      "created_at": "2026-10-23T10:47:00+03:00", "updated_at": "2026-10-23T10:47:00+03:00" }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 5, "total": 24 }
}
```

## Kapsam Dışı / Geliştirilebilir

- Birden fazla rol (ör. yönetici / sadece görüntüleme), kullanıcı yönetimi ekranı ve parola sıfırlama eklenebilir.
- Personel değişikliklerinin portala anlık iletilmesi için webhook veya kuyruk yapısı eklenebilir. Şu an kısa süreli önbellek (1 dk) kullanılıyor.
- Görevde belirtildiği gibi eğitim içeriği, sınav ve sertifika kapsam dışıdır.
