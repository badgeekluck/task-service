<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TaskBuilder;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'user_id',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_date' => 'date',
    ];

    public function newEloquentBuilder($query): TaskBuilder
    {
        return new TaskBuilder($query);
    }

    /**
     * Görevin sahibi olan kullanıcı ilişkisi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
