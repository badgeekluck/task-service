<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Bekliyor',
            self::InProgress => 'Devam Ediyor',
            self::Completed  => 'Tamamlandı',
            self::Cancelled  => 'İptal Edildi',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Cancelled => true,
            default                          => false,
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending    => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed  => [],
            self::Cancelled  => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
