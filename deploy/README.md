# Canlı Ortam (Docker)

| Uygulama | Adres | Konteyner | Yerel port |
|---|---|---|---|
| Personel Sistemi | https://personel.turkuazcode.com | `personel` | `127.0.0.1:8100` |
| Eğitim Portalı | https://egitim.turkuazcode.com | `egitim` | `127.0.0.1:8101` |

Yerel geliştirme Docker gerektirmez (bkz. ana README). Canlı sunucuda iki uygulama **Docker Compose** ile ayrı konteynerlerde çalışır:

```
İnternet ──HTTPS──► nginx (sunucu) ──► 127.0.0.1:8100 ─► [personel]  Apache + PHP 8.4 + SQLite (volume)
                                  └──► 127.0.0.1:8101 ─► [egitim]    Apache + PHP 8.4 + SQLite (volume)
                                                            │
                                                            └── http://personel/api/v1  (Docker iç ağı, token ile)
```

- Konteynerler yalnızca `127.0.0.1`'de dinler; dışarıya sunucudaki nginx açar, HTTPS sertifikası certbot ile alınır.
- Portal, personel API'sine Docker iç ağı üzerinden bağlanır; bu trafik sunucunun dışına çıkmaz. API, `https://personel.turkuazcode.com/api/v1` adresinden dışarıya da (token ile) açıktır.
- Veritabanları (`*-data`) ve log'lar (`*-logs`) Docker volume'lerinde durur; konteyner yeniden oluşturulsa da kaybolmaz.
- Konteyner her başladığında `migrate --force` ve `optimize` çalışır ([docker/entrypoint.sh](docker/entrypoint.sh)).

## Dosyalar

| Dosya | Görev |
|---|---|
| [docker-compose.yml](docker-compose.yml) | İki servis, portlar, volume'ler (proje adı `ardic-otomotiv`) |
| [docker/Dockerfile](docker/Dockerfile) | Ortak imaj; `APP` argümanı hangi uygulamanın derleneceğini seçer |
| [docker/entrypoint.sh](docker/entrypoint.sh) | Başlangıçta veritabanı/önbellek hazırlığı |
| [nginx/](nginx/) | Sunucudaki nginx site dosyaları (reverse proxy) |
| `env/personel.env`, `env/egitim.env` | Gizli ayarlar; **git'e girmez**, sunucuya ayrıca kopyalanır. Şablonlar: `*/.env.production.example` |

## İlk kurulum

```bash
# 1) Kod
mkdir -p /opt/ardic-otomotiv && cd /opt/ardic-otomotiv
git clone https://github.com/sukrucvll/ardic_otomotiv.git .

# 2) Gizli ayarlar: şablonları kopyalayıp APP_KEY ve ADMIN_PASSWORD doldurun
mkdir -p deploy/env
cp personel-sistemi/.env.production.example deploy/env/personel.env
cp egitim-portali/.env.production.example deploy/env/egitim.env
chmod 600 deploy/env/*.env
# APP_KEY üretmek için: echo "base64:$(openssl rand -base64 32)"

# 3) Personel Sistemi
cd deploy
docker compose up -d --build personel
docker compose exec personel php artisan db:seed --force              # yönetici + örnek personel
docker compose exec personel php artisan personnel:issue-token        # token'ı kopyalayın

# 4) Eğitim Portalı: token'ı env/egitim.env içindeki PERSONNEL_API_TOKEN'a yazın
docker compose up -d --build egitim
docker compose exec egitim php artisan db:seed --force                # yönetici + eğitimler + demo atamalar

# 5) nginx + HTTPS
cp nginx/personel.turkuazcode.com.conf /etc/nginx/sites-available/personel.turkuazcode.com
cp nginx/egitim.turkuazcode.com.conf /etc/nginx/sites-available/egitim.turkuazcode.com
ln -s /etc/nginx/sites-available/personel.turkuazcode.com /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/egitim.turkuazcode.com /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
certbot --nginx -d personel.turkuazcode.com --redirect
certbot --nginx -d egitim.turkuazcode.com --redirect
```

## Günlük işlemler (`/opt/ardic-otomotiv/deploy` içinde)

```bash
docker compose ps                                    # durum
docker compose logs -f egitim                        # Apache/PHP çıktısı
docker compose exec egitim tail -f storage/logs/laravel-$(date +%F).log

# .env değiştikten sonra (önbellek yeniden oluşur)
docker compose restart egitim

# Token yenileme
docker compose exec personel php artisan personnel:issue-token
#   → env/egitim.env içine yazın, ardından: docker compose restart egitim

# Yeni kod
cd /opt/ardic-otomotiv && git pull && cd deploy && docker compose up -d --build
```

> Sunucuda başka projeler de çalışıyor: bu projede yalnızca `docker compose` komutlarını **`deploy` klasörü içinde** çalıştırın (proje adı `ardic-otomotiv`). `docker system prune` kullanmayın.

## Kontrol

```bash
curl -I https://personel.turkuazcode.com/up          # 200
curl -I https://egitim.turkuazcode.com/up            # 200
curl -s https://personel.turkuazcode.com/api/v1/employees -H "Accept: application/json"   # 401 (token yok)
```

## Sorun giderme

| Belirti | Çözüm |
|---|---|
| `502 Bad Gateway` | Konteyner çalışmıyor: `docker compose ps`, `docker compose logs personel` |
| `500` hata sayfası | `docker compose exec <servis> tail -50 storage/logs/laravel-$(date +%F).log` |
| Portalda "Personel bilgileri şu anda alınamıyor" | Portal log'unda nedeni yazar (token geçersiz vb.). `env/egitim.env` düzeltilip `docker compose restart egitim` |
| CSS yüklenmiyor / sayfa `http://` linkleri üretiyor | nginx dosyasında `X-Forwarded-Proto` başlığının olduğunu kontrol edin |
