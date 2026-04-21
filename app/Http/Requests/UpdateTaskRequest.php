<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy kontrolü TaskController'da authorize() ile yapılıyor
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status'      => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'priority'    => ['sometimes', 'required', Rule::enum(TaskPriority::class)],
            'due_date'    => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum'   => 'Geçersiz durum. Geçerli değerler: ' . implode(', ', TaskStatus::values()),
            'priority.enum' => 'Geçersiz öncelik. Geçerli değerler: ' . implode(', ', TaskPriority::values()),
        ];
    }
}
