<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessTaskCreated implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 30;
    public int $maxExceptions = 2;

    public function __construct(
        public readonly string $taskId,
        public readonly string $userId,
    ) {}

    public function backoff(): array
    {
        return [5, 30, 90];
    }

    public function handle(): void
    {
        $task = Task::find($this->taskId);

        if ($task === null) {
            return;
        }

        Log::info('Task oluşturuldu', [
            'task_id'  => $task->id,
            'user_id'  => $task->user_id,
            'title'    => $task->title,
            'priority' => $task->priority->value,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ProcessTaskCreated başarısız', [
            'task_id'   => $this->taskId,
            'user_id'   => $this->userId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
