<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FilterTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'   => ['sometimes', 'nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'nullable', Rule::enum(TaskPriority::class)],
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum'   => 'Geçersiz durum filtresi. Geçerli değerler: ' . implode(', ', TaskStatus::values()),
            'priority.enum' => 'Geçersiz öncelik filtresi. Geçerli değerler: ' . implode(', ', TaskPriority::values()),
            'per_page.max'  => 'Sayfa başına en fazla 100 kayıt getirilebilir.',
        ];
    }
}
