#!/bin/sh
# Konteyner her başladığında: SQLite dosyasını hazırla, tabloları güncelle,
# ayar/rota/görünüm önbelleklerini .env'e göre yeniden oluştur.
set -e

mkdir -p "$(dirname "$DB_DATABASE")" storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
[ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"

php artisan migrate --force
php artisan optimize

# Artisan root olarak çalıştı; web sunucusu (www-data) bu dosyalara yazabilmeli
chown -R www-data:www-data "$(dirname "$DB_DATABASE")" storage bootstrap/cache

exec apache2-foreground
