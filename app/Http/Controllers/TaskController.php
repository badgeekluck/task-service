<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\DeleteTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\DTOs\TaskData;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
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
        private readonly CreateTaskAction $createTask,
        private readonly UpdateTaskAction $updateTask,
        private readonly DeleteTaskAction $deleteTask,
    ) {}

    /**
     * GET /api/tasks
     * Kullanıcının görev listesi — filtreli ve sayfalı.
     */
    public function index(FilterTaskRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->forUser($request->user()->id)
            ->when(
                $request->validated('status'),
                fn ($q, $v) => $q->withStatus(TaskStatus::from($v))
            )
            ->when(
                $request->validated('priority'),
                fn ($q, $v) => $q->withPriority(TaskPriority::from($v))
            )
            ->when(
                $request->validated('search'),
                fn ($q, $v) => $q->search($v)
            )
            ->latest()
            ->paginate($request->validated('per_page', 15));

        return TaskResource::collection($tasks);
    }

    /**
     * POST /api/tasks
     * Yeni görev oluştur.
     */
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

    /**
     * GET /api/tasks/{task}
     * Tek görev detayı.
     */
    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    /**
     * PATCH /api/tasks/{task}
     * Görev güncelle — partial update (PATCH semantiği).
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = array_merge([
            'title'       => $task->title,
            'description' => $task->description,
            'status'      => $task->status->value,
            'priority'    => $task->priority->value,
            'due_date'    => $task->due_date?->toDateString(),
        ], $request->validated());

        $task = $this->updateTask->execute(
            task: $task,
            data: TaskData::fromRequest($validated),
        );

        return (new TaskResource($task))
            ->additional(['message' => 'Görev başarıyla güncellendi.']);
    }

    /**
     * DELETE /api/tasks/{task}
     * Görevi sil — soft delete (geri alınabilir).
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->deleteTask->execute($task);

        return response()->json(null, 204);
    }
}
