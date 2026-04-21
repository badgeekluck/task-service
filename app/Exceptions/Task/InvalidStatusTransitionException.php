<?php

declare(strict_types=1);

namespace App\Exceptions\Task;

use App\Enums\TaskStatus;
use RuntimeException;

final class InvalidStatusTransitionException extends RuntimeException
{
    public function __construct(
        public readonly TaskStatus $from,
        public readonly TaskStatus $to,
    ) {
        parent::__construct(
            message: sprintf(
                '"%s" durumundan "%s" durumuna geçiş yapılamaz.',
                $from->label(),
                $to->label(),
            ),
        );
    }
}
