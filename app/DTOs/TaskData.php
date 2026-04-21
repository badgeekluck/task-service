<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;

/**
 * Task veri transfer nesnesi.
 *
 * PHP 8.4 readonly class — oluşturulduktan sonra hiçbir alan değiştirilemez. Bu sayede Action'lara geçen veri yanlışlıkla mutasyona uğrayamaz.
 */
final readonly class TaskData
{
    public function __construct(
        public string           $title,
        public TaskStatus       $status,
        public TaskPriority     $priority,
        public ?string          $description = null,
        public ?CarbonImmutable $dueDate     = null,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            title:       $validated['title'],
            status:      TaskStatus::from($validated['status'] ?? TaskStatus::Pending->value),
            priority:    TaskPriority::from($validated['priority'] ?? TaskPriority::Medium->value),
            description: $validated['description'] ?? null,
            dueDate:     isset($validated['due_date'])
                             ? CarbonImmutable::parse($validated['due_date'])
                             : null,
        );
    }
}
