<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\DTOs\TaskData;
use App\Exceptions\Task\InvalidStatusTransitionException;
use App\Models\Task;

final readonly class UpdateTaskAction
{
    /**
     * @param array<string, mixed> $validated  PATCH isteğinden gelen validated alanlar
     *
     * @throws InvalidStatusTransitionException  Geçersiz durum geçişi
     */
    public function execute(Task $task, array $validated): Task
    {
        $data = TaskData::fromRequest(array_merge([
            'title'       => $task->title,
            'description' => $task->description,
            'status'      => $task->status->value,
            'priority'    => $task->priority->value,
            'due_date'    => $task->due_date?->toDateString(),
        ], $validated));

        // State machine koruması — terminal durumdan çıkış yasak
        $this->ensureValidTransition($task, $data);

        $task->update([
            'title'       => $data->title,
            'description' => $data->description,
            'status'      => $data->status->value,
            'priority'    => $data->priority->value,
            'due_date'    => $data->dueDate,
        ]);

        return $task->refresh();
    }

    private function ensureValidTransition(Task $task, TaskData $data): void
    {
        $current = $task->status;
        $next    = $data->status;

        if ($current === $next) {
            return; // Durum değişmiyorsa kontrol gerekmez
        }

        if (! $current->canTransitionTo($next)) {
            throw new InvalidStatusTransitionException(from: $current, to: $next);
        }
    }
}
