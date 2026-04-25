<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'title'       => fake()->sentence(nbWords: 4, variableNbWords: true),
            'description' => fake()->optional(0.7)->paragraph(),
            'status'      => TaskStatus::Pending,
            'priority'    => fake()->randomElement(TaskPriority::cases()),
            'due_date'    => fake()->optional(0.6)->dateTimeBetween('now', '+3 months'),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => TaskStatus::Pending]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => TaskStatus::InProgress]);
    }

    public function completed(): static
    {
        return $this->state(['status' => TaskStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => TaskStatus::Cancelled]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => TaskPriority::High]);
    }

    public function critical(): static
    {
        return $this->state(['priority' => TaskPriority::Critical]);
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date' => fake()->dateTimeBetween('-2 months', '-1 day'),
            'status'   => TaskStatus::Pending,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
