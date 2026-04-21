<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\DeleteTaskAction;
use App\Actions\Task\ListTasksAction;
use App\Actions\Task\UpdateTaskAction;
use App\DTOs\TaskData;
use App\DTOs\TaskFilters;
use App\Http\Requests\FilterTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TaskController extends Controller
{
    public function __construct(
        private readonly ListTasksAction   $listTasks,
        private readonly CreateTaskAction  $createTask,
        private readonly UpdateTaskAction  $updateTask,
        private readonly DeleteTaskAction  $deleteTask,
    ) {}

    /** GET /api/v1/tasks */
    public function index(FilterTaskRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->listTasks->execute(
            user:    $request->user(),
            filters: TaskFilters::fromRequest($request->validated()),
        );

        return TaskResource::collection($tasks);
    }

    /** POST /api/v1/tasks */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $task = $this->createTask->execute(
            user: $request->user(),
            data: TaskData::fromRequest($request->validated()),
        );

        return (new TaskResource($task))
            ->additional(['message' => 'Görev başarıyla oluşturuldu.'])
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/tasks/{task} */
    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    /** PATCH /api/v1/tasks/{task} */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task = $this->updateTask->execute($task, $request->validated());

        return (new TaskResource($task))
            ->additional(['message' => 'Görev başarıyla güncellendi.']);
    }

    /** DELETE /api/v1/tasks/{task} */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->deleteTask->execute($task);

        return response()->json(null, 204);
    }
}
