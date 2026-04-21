<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;

class TaskBuilder extends Builder
{
    public function forUser(int|string $userId): self
    {
        return $this->where('user_id', $userId);
    }

    public function withStatus(?TaskStatus $status): self
    {
        return $this->when($status, fn($q) => $q->where('status', $status->value));
    }

    public function withPriority(?TaskPriority $priority): self
    {
        return $this->when($priority, fn($q) => $q->where('priority', $priority->value));
    }

    public function search(string $term): self
    {
        return $this->where(function ($query) use ($term) {
            $query->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function orderByPriority(): self
    {
        return $this->orderByRaw("
            CASE priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
                ELSE 4
            END
        ");
    }
}
