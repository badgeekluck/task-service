<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');
        return $this->user()->can('update', $task);
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status'      => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'priority'    => ['sometimes', 'required', Rule::enum(TaskPriority::class)],
            'due_date'    => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
