<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Kullanıcı listelemeyi görebilir mi?
     * Sisteme giriş yapmış herkes kendi listesini görebilir.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Kullanıcı bu tekil görevi görebilir mi?
     */
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Kullanıcı yeni görev oluşturabilir mi?
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Kullanıcı bu görevi güncelleyebilir mi?
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Kullanıcı bu görevi silebilir mi?
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
