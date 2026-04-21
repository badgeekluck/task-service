<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\DTOs\TaskData;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class CreateTaskAction
{
    public function execute(User $user, TaskData $data): Task
    {
        return DB::transaction(function () use ($user, $data) {
            
            $task = Task::create([
                'user_id'     => $user->id,
                'title'       => $data->title,
                'description' => $data->description,
                'status'      => $data->status->value,   // Enum'dan string'e
                'priority'    => $data->priority->value, // Enum'dan string'e
                'due_date'    => $data->dueDate,
            ]);

            return $task;
            
        });
    }
}