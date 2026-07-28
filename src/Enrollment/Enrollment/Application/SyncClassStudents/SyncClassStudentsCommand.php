<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Application\SyncClassStudents;

final readonly class SyncClassStudentsCommand
{
    public function __construct(
        public ?array $classIds = null,
        public string $triggeredBy = 'scheduler',
    ) {}
}
