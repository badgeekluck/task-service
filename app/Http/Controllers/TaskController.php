<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\DeleteTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\DTOs\TaskData;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->forUser($request->user()->id)
            ->when(
                $request->string('status')->isNotEmpty(),
                fn ($q) => $q->withStatus(TaskStatus::from($request->string('status')->value()))
            )
            ->when(
                $request->string('priority')->isNotEmpty(),
                fn ($q) => $q->withPriority(TaskPriority::from($request->string('priority')->value()))
            )
            ->when(
                $request->string('search')->isNotEmpty(),
                fn ($q) => $q->search($request->string('search')->value())
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($tasks);
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

        return response()->json([
            'message' => 'Görev başarıyla oluşturuldu.',
            'data'    => $task,
        ], 201);
    }

    /**
     * GET /api/tasks/{task}
     * Tek görev detayı.
     */
    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json(['data' => $task]);
    }

    /**
     * PATCH /api/tasks/{task}
     * Görev güncelle — partial update (PATCH semantiği).
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        // Gönderilmeyen alanlar mevcut değerleriyle doldurulur
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

        return response()->json([
            'message' => 'Görev başarıyla güncellendi.',
            'data'    => $task,
        ]);
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
