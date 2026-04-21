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
    // ULID kullanımı (Güvenlik ve Performans), SoftDeletes (Çöp kutusu)
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'user_id',
    ];

    // Veritabanından çıkan verileri otomatik olarak Enum ve Date nesnelerine çevirir
    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_date' => 'date',
    ];

    /**
     * Controller'da spagetti sorgular yazmamak için
     * Eloquent'e kendi yazdığımız TaskBuilder'ı kullanmasını söylüyoruz.
     */
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
