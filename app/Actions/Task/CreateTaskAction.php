<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\DTOs\TaskData;
use App\Jobs\ProcessTaskCreated;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class CreateTaskAction
{
    public function __construct(
        private TaskCacheService $cache,
    ) {}

    public function execute(User $user, TaskData $data): Task
    {
        // Transaction içinde: INSERT + job dispatch birlikte atomik.
        // afterCommit() garantisi: INSERT commit olmadan job kuyruğa girmez.
        // Transaction rollback olursa (örn. DB constraint) job hiç dispatch edilmez.
        $task = DB::transaction(function () use ($user, $data): Task {
            $task = Task::create([
                'user_id'     => $user->id,
                'title'       => $data->title,
                'description' => $data->description,
                'status'      => $data->status->value,
                'priority'    => $data->priority->value,
                'due_date'    => $data->dueDate,
            ]);

            ProcessTaskCreated::dispatch($task->id, $user->id)->afterCommit();

            return $task;
        });

        // Cache side effect — transaction dışında, bağımsız.
        // Redis çökse bile task oluşturuldu; 201 dönmeli.
        try {
            $this->cache->invalidate($user->id);
        } catch (Throwable $e) {
            Log::warning("Cache temizlenemedi: {$e->getMessage()}", [
                'user_id' => $user->id,
                'task_id' => $task->id,
            ]);
        }

        return $task;
    }
}
