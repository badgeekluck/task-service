<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\DTOs\UpdateTaskData;
use App\Exceptions\Task\InvalidStatusTransitionException;
use App\Models\Task;
use App\Services\TaskCacheService;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class UpdateTaskAction
{
    public function __construct(
        private TaskCacheService $cache,
    ) {}

    /**
     * @throws InvalidStatusTransitionException  Geçersiz durum geçişi
     */
    public function execute(Task $task, UpdateTaskData $data): Task
    {
        // State machine kontrolü — sadece status değişiyorsa çalışır.
        $this->ensureValidTransition($task, $data);

        // Sadece gönderilen alanları güncelle (race condition önlemi).
        $task->update($data->toArray());

        // Cache side effect — güncelleme başarılı, Redis çökse bile 200 dönmeli.
        try {
            $this->cache->invalidate($task->user_id);
        } catch (Throwable $e) {
            Log::warning("Task güncellendi ancak cache temizlenemedi: {$e->getMessage()}", [
                'user_id' => $task->user_id,
                'task_id' => $task->id,
            ]);
        }

        // update() model'i bellekte zaten günceller — refresh() ile ekstra SELECT gerekmez.
        return $task;
    }

    private function ensureValidTransition(Task $task, UpdateTaskData $data): void
    {
        if ($data->status === null) {
            return; // Status değişmiyorsa kontrol gerekmez
        }

        if ($task->status === $data->status) {
            return; // Aynı durum, geçiş yok
        }

        if (! $task->status->canTransitionTo($data->status)) {
            throw new InvalidStatusTransitionException(from: $task->status, to: $data->status);
        }
    }
}
