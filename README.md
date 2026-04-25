# Task Service

Laravel 13 + PHP 8.4

## Stack

- **PHP 8.4** — readonly class, typed properties
- **Laravel 13** — framework
- **FrankenPHP + Octane** — yüksek performanslı sunucusu
- **PostgreSQL** — veritabanı
- **Laravel Sanctum** — token tabanlı kimlik doğrulama
- **Pest** — test framework

## Kurulum

```bash
# Repoyu klonla
git clone <repo-url>
cd task-service

# .env dosyasını oluştur
cp .env.example .env

# Docker ile çalıştır
docker compose up -d

# Migration + seed (test verisi)
docker compose exec app php artisan migrate:fresh --seed
```

Seed sonrası hazır kullanıcılar:

| Email | Şifre |
|-------|-------|
| harun@test.com | password |
| other@test.com | password |

## API Endpoints

Tüm endpointler `/api/v1/` prefix'i ile çalışır.

### Auth

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| POST | `/register` | Yeni kullanıcı kaydı |
| POST | `/login` | Giriş, token döner |
| POST | `/logout` | Token'ı siler |

### Tasks (auth gerekli)

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/tasks` | Task listesi (filtrelenebilir) |
| POST | `/tasks` | Yeni task oluştur |
| GET | `/tasks/{id}` | Tek task |
| PATCH | `/tasks/{id}` | Güncelle |
| DELETE | `/tasks/{id}` | Sil (soft delete) |

**Filtreler:** `?status=pending&priority=high&search=kelime&per_page=15`

Tüm isteklerde header olarak `Accept: application/json` gönderilmeli.

## Mimari Kararlar

### Action Pattern (SRP)
Her işlem kendi Action sınıfında. Controller sadece isteği alıp Action'a iletir, response döner. Bussiness logic controller'da değil.

```
ListTasksAction   → filtreleme + sayfalama
CreateTaskAction  → DB transaction ile oluşturma
UpdateTaskAction  → PATCH semantiği, state machine kontrolü
DeleteTaskAction  → soft delete
```

### ULID Primary Key
UUID yerine ULID tercih edildi. ULID sıralı (time-sortable) olduğu için PostgreSQL'de B-tree index'lerinde daha iyi performans verir, aynı zamanda unique olduğu için güvenli.

### Composite Index
```sql
(user_id, status, created_at)
```
Task listesindeki en yaygın sorgu: belirli kullanıcının belirli statüsteki task'larını tarihe göre sıralama. Bu index full table scan'i engeller.

### State Machine
Task status'u rastgele değiştirilemez. `TaskStatus` enum'u izin verilen geçişleri tanımlar:

```
pending → in_progress → completed
pending → cancelled
in_progress → cancelled
```
`completed` ve `cancelled` terminal durum — geri dönüş yok.

### PHP 8.4 readonly DTO
Validation sonrası veri `TaskData` ve `TaskFilters` DTO'larına dönüştürülür. `final readonly class` sayesinde bu objeler immutable — bir kez oluşturulunca değiştirilemez.

### Policy (IDOR/BOLA Koruması)
Her task işleminde `TaskPolicy` devreye girer. Kullanıcı sadece kendi task'larına erişebilir. `user_id` eşleşmezse 403 döner.

### Rate Limiting
- `/register` ve `/login`: dakikada 10 istek (IP bazlı)
- Diğer tüm endpointler: dakikada 60 istek (kullanıcı bazlı)

### OPcache + JIT
`opcache.ini` ile production için yapılandırıldı: `validate_timestamps=0`, `jit=tracing`, `jit_buffer_size=128M`. İlk istek sonrası PHP bytecode bellekte kalır, tekrar derlenmez.

## Testler

```bash
# Tüm testleri çalıştır
docker compose exec app ./vendor/bin/pest

# Sadece unit testler
docker compose exec app ./vendor/bin/pest --testsuite=Unit

# Sadece feature testler
docker compose exec app ./vendor/bin/pest --testsuite=Feature
```

29 feature test (auth, CRUD, güvenlik, status geçişleri) + 12 unit test case (state machine).
