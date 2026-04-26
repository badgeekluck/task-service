#!/bin/sh
set -e

echo "Veritabanı bağlantısı kontrol ediliyor..."
until nc -z db 5432; do
  echo "Veritabanı henüz hazır değil, bekleniyor..."
  sleep 2
done
echo "Veritabanı hazır!"

# Migrate ve optimize sadece app container'ında çalışır.
# Worker container'ı "php artisan queue:work" ile başlar — migrate atlanır.
if [ "$1" != "php" ] || [ "$3" != "queue:work" ]; then
  echo "Migration'lar çalıştırılıyor..."
  php artisan migrate --force

  echo "Cache optimize ediliyor..."
  php artisan optimize
fi

exec "$@"
