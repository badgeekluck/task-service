#!/bin/sh
set -e

echo "Veritabanı bağlantısı kontrol ediliyor..."
until nc -z -v -w30 db 5432
do
  echo "Veritabanı henüz hazır değil, bekleniyor..."
  sleep 2
done
echo "Veritabanı hazır!"

echo "Migration'lar çalıştırılıyor..."
php artisan migrate --force

php artisan optimize

# 4. Asıl komutu başlat
exec "$@"