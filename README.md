# Task Service

Laravel 13 + PHP 8.4 ile yazılmış RESTful görev yönetimi API'si.

## Stack

- **PHP 8.4** — readonly class, typed properties
- **Laravel 13** — framework
- **FrankenPHP + Octane** — yüksek performanslı uygulama sunucusu
- **PostgreSQL** — veritabanı
- **Redis** — cache ve queue
- **Laravel Sanctum** — token tabanlı kimlik doğrulama
- **Pest** — test framework

## Kurulum

```bash
# Repoyu klonla
git clone <repo-url>
cd task-service

# .env dosyasını oluştur
cp .env.example .env

# Docker ile çalıştır (app + worker + db + redis otomatik başlar)
docker compose up -d

# Migration + seed (test verisi)
docker compose exec app php artisan migrate:fresh --seed
```

Seed sonrası hazır kullanıcılar:

| Email | Şifre |
|-------|-------|
| harun@test.com | password |
| other@test.com | password |

Insomnia collection dosyası: `insomnia-collection.json` — import edip direkt test edebilirsin.

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

Tüm isteklerde `Accept: application/json` header'ı gönderilmeli.

## Mimari Kararlar

### Action Pattern (SRP)
Her işlem kendi Action sınıfında. Controller sadece isteği alıp Action'a iletir, response döner. Business logic controller'da değil.

```
ListTasksAction   → cache + filtreleme + sayfalama
CreateTaskAction  → DB transaction + cache invalidation + job dispatch
UpdateTaskAction  → PATCH semantiği + state machine kontrolü + cache invalidation
DeleteTaskAction  → soft delete + cache invalidation
```

### ULID Primary Key
UUID yerine ULID tercih edildi. ULID sıralı (time-sortable) olduğu için PostgreSQL'de B-tree index'lerinde daha iyi performans verir, aynı zamanda tahmin edilemez olduğu için güvenlidir.

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

### Redis Cache — ID-Only Pattern

`LengthAwarePaginator` nesnesi closure içerdiği için Redis'e doğrudan serialize edilemez. Bu sorunu çözmek için **sadece ID listesi ve toplam sayı** cache'lenir:

```php
// Cache'lenen şey: primitif veri (serialize edilebilir)
[$ids, $total] = Cache::tags(["user:{$user->id}:tasks"])
    ->remember($cacheKey, 60, function () {
        return [$ids, $total]; // string[] + int
    });

// Cache'ten sonra: modeller ID ile çekiliyor, PHP'de sıralanıyor
$items = Task::whereIn('id', $ids)
    ->get()
    ->sortBy(fn ($task) => array_search($task->id, $ids))
    ->values();
```

**Cache key:** `tasks:user:{id}:{md5(filtreler)}:page:{n}` — aynı filtre kombinasyonu aynı key'i üretir.

**Tag-based invalidation:** `Cache::tags(["user:{$userId}:tasks"])->flush()` ile kullanıcının tüm cache sayfaları tek komutla temizlenir. Create/update/delete sonrası `TaskCacheService::invalidate()` çağrılır.

### Redis Queue — Async Job

Task oluşturulunca `ProcessTaskCreated` job'ı Redis queue'ya gönderilir:

```php
// CreateTaskAction içinde
ProcessTaskCreated::dispatch($task);
// API burada durmuyor — 201 döner, job arka planda işlenir
```

Worker container bu job'ı async olarak tüketir. API response süresi job'ın süresinden bağımsız. `docker compose up -d` ile worker container `restart: unless-stopped` politikasıyla otomatik başlar — ayrıca komut çalıştırmak gerekmez.

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
