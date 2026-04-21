<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\DTOs\TaskFilters;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListTasksAction
{
    public function execute(User $user, TaskFilters $filters): LengthAwarePaginator
    {
        return Task::query()
            ->forUser($user->id)
            ->when($filters->status,   fn ($q, $v) => $q->withStatus($v))
            ->when($filters->priority, fn ($q, $v) => $q->withPriority($v))
            ->when($filters->search,   fn ($q, $v) => $q->search($v))
            ->latest()
            ->paginate($filters->perPage);
    }
}
