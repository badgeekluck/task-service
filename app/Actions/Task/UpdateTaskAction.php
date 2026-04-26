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

    /** @throws InvalidStatusTransitionException */
    public function execute(Task $task, UpdateTaskData $data): Task
    {
        $this->ensureValidTransition($task, $data);

        $task->update($data->toArray());

        try {
            $this->cache->invalidate($task->user_id);
        } catch (Throwable $e) {
            Log::warning('Cache temizlenemedi: ' . $e->getMessage(), [
                'user_id' => $task->user_id,
                'task_id' => $task->id,
            ]);
        }

        return $task;
    }

    private function ensureValidTransition(Task $task, UpdateTaskData $data): void
    {
        if ($data->status === null || $task->status === $data->status) {
            return;
        }

        if (! $task->status->canTransitionTo($data->status)) {
            throw new InvalidStatusTransitionException(from: $task->status, to: $data->status);
        }
    }
}
