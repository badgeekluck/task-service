<?php

declare(strict_types=1);

use App\Enums\TaskStatus;

describe('TaskStatus state machine', function () {

    describe('izin verilen geçişler', function () {
        it('belirtilen duruma geçiş yapabilir', function (TaskStatus $from, TaskStatus $to) {
            expect($from->canTransitionTo($to))->toBeTrue();
        })->with([
            'pending → in_progress'   => [TaskStatus::Pending, TaskStatus::InProgress],
            'pending → cancelled'     => [TaskStatus::Pending, TaskStatus::Cancelled],
            'in_progress → completed' => [TaskStatus::InProgress, TaskStatus::Completed],
            'in_progress → cancelled' => [TaskStatus::InProgress, TaskStatus::Cancelled],
        ]);
    });

    describe('yasak geçişler', function () {
        it('belirtilen duruma geçiş yapamaz', function (TaskStatus $from, TaskStatus $to) {
            expect($from->canTransitionTo($to))->toBeFalse();
        })->with([
            'completed → pending'     => [TaskStatus::Completed, TaskStatus::Pending],
            'completed → in_progress' => [TaskStatus::Completed, TaskStatus::InProgress],
            'cancelled → pending'     => [TaskStatus::Cancelled, TaskStatus::Pending],
            'cancelled → in_progress' => [TaskStatus::Cancelled, TaskStatus::InProgress],
        ]);
    });

    describe('terminal durumlar', function () {
        it('doğru terminal durumu döndürür', function (TaskStatus $status, bool $isTerminal) {
            expect($status->isTerminal())->toBe($isTerminal);
        })->with([
            'completed terminal dur'     => [TaskStatus::Completed, true],
            'cancelled terminal dur'     => [TaskStatus::Cancelled, true],
            'pending terminal değil'     => [TaskStatus::Pending, false],
            'in_progress terminal değil' => [TaskStatus::InProgress, false],
        ]);
    });

});
