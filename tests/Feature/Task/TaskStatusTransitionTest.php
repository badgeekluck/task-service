<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;

describe('Status Transitions', function () {

    it('allows pending → in_progress', function () {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->pending()->create();

        $this->actingAs($user)
             ->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'in_progress'])
             ->assertStatus(200)
             ->assertJsonPath('data.status.value', 'in_progress');
    });

    it('allows in_progress → completed', function () {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->inProgress()->create();

        $this->actingAs($user)
             ->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'completed'])
             ->assertStatus(200)
             ->assertJsonPath('data.status.value', 'completed');
    });

    it('allows pending → cancelled', function () {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->pending()->create();

        $this->actingAs($user)
             ->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'cancelled'])
             ->assertStatus(200);
    });

    it('rejects completed → pending (422)', function () {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->completed()->create();

        $this->actingAs($user)
             ->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'pending'])
             ->assertStatus(422)
             ->assertJsonStructure(['message', 'errors']);
    });

    it('rejects cancelled → in_progress (422)', function () {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->cancelled()->create();

        $this->actingAs($user)
             ->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'in_progress'])
             ->assertStatus(422);
    });

});
