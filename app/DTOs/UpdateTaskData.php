<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;

final readonly class UpdateTaskData
{
    public function __construct(
        public ?string          $title          = null,
        public ?TaskStatus      $status         = null,
        public ?TaskPriority    $priority       = null,
        public ?string          $description    = null,
        public ?CarbonImmutable $dueDate        = null,
        private bool            $hasDescription = false,
        private bool            $hasDueDate     = false,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            title:          $validated['title'] ?? null,
            status:         isset($validated['status'])
                                ? TaskStatus::from($validated['status'])
                                : null,
            priority:       isset($validated['priority'])
                                ? TaskPriority::from($validated['priority'])
                                : null,
            description:    $validated['description'] ?? null,
            dueDate:        isset($validated['due_date'])
                                ? CarbonImmutable::parse($validated['due_date'])
                                : null,
            hasDescription: array_key_exists('description', $validated),
            hasDueDate:     array_key_exists('due_date', $validated),
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }

        if ($this->priority !== null) {
            $data['priority'] = $this->priority->value;
        }

        if ($this->hasDescription) {
            $data['description'] = $this->description;
        }

        if ($this->hasDueDate) {
            $data['due_date'] = $this->dueDate?->toDateString();
        }

        return $data;
    }
}
