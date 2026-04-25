<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class TaskCacheService
{
    /**
     * Kullanıcının tüm task cache'ini temizle.
     * Create / update / delete sonrası çağrılır.
     */
    public function invalidate(string $userId): void
    {
        Cache::tags(["user:{$userId}:tasks"])->flush();
    }
}
