<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\DTOs\TaskFilters;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Facades\Cache;

final class ListTasksAction
{
    private const TTL = 60;

    public function execute(
        User $user,
        TaskFilters $filters,
        int $page = 1,
        string $path = '/',
        array $queryString = []
    ): LengthAwarePaginator {
        $page     = max(1, $page);
        $cacheKey = $this->buildCacheKey($user->id, $filters, $page);

        [$ids, $total] = Cache::tags(["user:{$user->id}:tasks"])
            ->remember($cacheKey, self::TTL, function () use ($user, $filters, $page) {
                $query = Task::query()
                    ->forUser($user->id)
                    ->when($filters->status,   fn ($q, $v) => $q->withStatus($v))
                    ->when($filters->priority, fn ($q, $v) => $q->withPriority($v))
                    ->when($filters->search,   fn ($q, $v) => $q->search($v))
                    ->latest();

                $total = $query->count();
                $ids   = (clone $query)
                    ->forPage($page, $filters->perPage)
                    ->pluck('id')
                    ->all();

                return [$ids, $total];
            });

        if (empty($ids)) {
            return new ConcretePaginator([], $total, $filters->perPage, $page, [
                'path'  => $path,
                'query' => $queryString,
            ]);
        }

        $items = Task::whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($task) => array_search($task->id, $ids))
            ->values();

        return new ConcretePaginator(
            items:       $items,
            total:       $total,
            perPage:     $filters->perPage,
            currentPage: $page,
            options:     ['path' => $path, 'query' => $queryString],
        );
    }

    private function buildCacheKey(int|string $userId, TaskFilters $filters, int $page): string
    {
        return sprintf(
            'tasks:user:%s:%s:page:%d',
            $userId,
            md5(serialize([
                'status'   => $filters->status?->value,
                'priority' => $filters->priority?->value,
                'search'   => $filters->search,
                'per_page' => $filters->perPage,
            ])),
            $page,
        );
    }
}
