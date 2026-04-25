<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

describe('Task List', function () {

    it('returns only authenticated user tasks', function () {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Task::factory(3)->forUser($user)->create();
        Task::factory(2)->forUser($other)->create();

        $this->actingAs($user)
             ->getJson('/api/v1/tasks')
             ->assertStatus(200)
             ->assertJsonCount(3, 'data');
    });

    it('returns 401 without token', function () {
        $this->getJson('/api/v1/tasks')
             ->assertStatus(401);
    });

    it('filters by status', function () {
        $user = User::factory()->create();

        Task::factory(2)->forUser($user)->pending()->create();
        Task::factory(3)->forUser($user)->inProgress()->create();

        $this->actingAs($user)
             ->getJson('/api/v1/tasks?status=pending')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    });

    it('filters by priority', function () {
        $user = User::factory()->create();

        Task::factory(2)->forUser($user)->highPriority()->create();
        Task::factory(3)->forUser($user)->create(['priority' => TaskPriority::Low]);

        $this->actingAs($user)
             ->getJson('/api/v1/tasks?priority=high')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    });

    it('rejects invalid status filter with 422', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->getJson('/api/v1/tasks?status=invalid')
             ->assertStatus(422);
    });

});

describe('Task Create', function () {

    it('creates a task and returns 201', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->postJson('/api/v1/tasks', [
                 'title'       => 'Test görevi',
                 'description' => 'Açıklama',
                 'priority'    => 'high',
                 'due_date'    => '2026-12-01',
             ])
             ->assertStatus(201)
             ->assertJsonPath('data.title', 'Test görevi')
             ->assertJsonPath('data.status.value', 'pending');
    });

    it('fails without title', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->postJson('/api/v1/tasks', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
    });

    it('returns 401 without token', function () {
        $this->postJson('/api/v1/tasks', ['title' => 'Test'])
             ->assertStatus(401);
    });

});

describe('Task Show', function () {

    it('returns a task', function () {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $this->actingAs($user)
             ->getJson("/api/v1/tasks/{$task->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $task->id);
    });

    it('returns 403 for another user task', function () {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $task  = Task::factory()->forUser($other)->create();

        $this->actingAs($user)
             ->getJson("/api/v1/tasks/{$task->id}")
             ->assertStatus(403);
    });

    it('returns 404 for non-existent task', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->getJson('/api/v1/tasks/non-existent-id')
             ->assertStatus(404);
    });

});

describe('Task Update', function () {

    it('updates a task', function () {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->pending()->create();

        $this->actingAs($user)
             ->patchJson("/api/v1/tasks/{$task->id}", [
                 'status' => 'in_progress',
             ])
             ->assertStatus(200)
             ->assertJsonPath('data.status.value', 'in_progress');
    });

    it('returns 403 for another user task', function () {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $task  = Task::factory()->forUser($other)->create();

        $this->actingAs($user)
             ->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Hack'])
             ->assertStatus(403);
    });

});

describe('Task Delete', function () {

    it('deletes a task and returns 204', function () {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $this->actingAs($user)
             ->deleteJson("/api/v1/tasks/{$task->id}")
             ->assertStatus(204);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    });

    it('returns 403 for another user task', function () {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $task  = Task::factory()->forUser($other)->create();

        $this->actingAs($user)
             ->deleteJson("/api/v1/tasks/{$task->id}")
             ->assertStatus(403);
    });

});
