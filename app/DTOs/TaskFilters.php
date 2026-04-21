<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;

final readonly class TaskFilters
{
    public function __construct(
        public ?TaskStatus   $status   = null,
        public ?TaskPriority $priority = null,
        public ?string       $search   = null,
        public int           $perPage  = 15,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            status:   isset($validated['status'])
                          ? TaskStatus::from($validated['status'])
                          : null,
            priority: isset($validated['priority'])
                          ? TaskPriority::from($validated['priority'])
                          : null,
            search:   $validated['search'] ?? null,
            perPage:  $validated['per_page'] ?? 15,
        );
    }
}
