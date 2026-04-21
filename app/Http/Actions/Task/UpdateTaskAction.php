<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\DTOs\TaskData;
use App\Models\Task;

readonly class UpdateTaskAction
{
    public function execute(Task $task, TaskData $data): Task
    {
        $task->update([
            'title'       => $data->title,
            'description' => $data->description,
            'status'      => $data->status->value,
            'priority'    => $data->priority->value,
            'due_date'    => $data->dueDate,
        ]);

        return $task->refresh(); // Güncel veriyi DB'den alıp döndürürüz
    }
}
