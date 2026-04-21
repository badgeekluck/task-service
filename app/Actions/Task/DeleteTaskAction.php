<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\Task;

readonly class DeleteTaskAction
{
    public function execute(Task $task): bool
    {
        return $task->delete();
    }
}
