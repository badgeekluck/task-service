<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status'      => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority'    => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date'    => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'          => 'Görev başlığı zorunludur.',
            'title.min'               => 'Başlık en az :min karakter olmalıdır.',
            'status.enum'             => 'Geçersiz durum. Geçerli değerler: ' . implode(', ', TaskStatus::values()),
            'priority.enum'           => 'Geçersiz öncelik. Geçerli değerler: ' . implode(', ', TaskPriority::values()),
            'due_date.after_or_equal' => 'Bitiş tarihi geçmiş bir zaman olamaz.',
        ];
    }
}