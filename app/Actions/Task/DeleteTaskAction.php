<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\Task;
use App\Services\TaskCacheService;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class DeleteTaskAction
{
    public function __construct(
        private TaskCacheService $cache,
    ) {}

    public function execute(Task $task): void
    {
        $userId = $task->user_id;
        $taskId = $task->id;

        $task->delete();

        // Cache side effect — soft delete başarılı, Redis çökse bile 204 dönmeli.
        try {
            $this->cache->invalidate($userId);
        } catch (Throwable $e) {
            Log::warning("Task silindi ancak cache temizlenemedi: {$e->getMessage()}", [
                'user_id' => $userId,
                'task_id' => $taskId,
            ]);
        }
    }
}
