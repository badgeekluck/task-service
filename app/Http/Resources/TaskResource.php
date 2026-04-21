<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Task API dönüştürücüsü (Transformer).
 *
 * Neden Resource?
 * - DB sütun adları dışarıya sızmaz.
 * - Enum'lar hem makine okunabilir (value) hem insan okunabilir (label) döner.
 * - API şeması DB şemasından bağımsız değişebilir.
 *
 * @mixin Task
 */
final class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Task $this */
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,

            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],

            'priority' => [
                'value'  => $this->priority->value,
                'label'  => $this->priority->label(),
                'weight' => $this->priority->weight(),
            ],

            'due_date'   => $this->due_date?->toDateString(),
            'is_overdue' => $this->due_date
                                && ! $this->status->isTerminal()
                                && $this->due_date->isPast(),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
