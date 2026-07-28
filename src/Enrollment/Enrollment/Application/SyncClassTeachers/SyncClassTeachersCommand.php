<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Application\SyncClassTeachers;

final readonly class SyncClassTeachersCommand
{
    public function __construct(
        public ?array $classIds = null,
        public string $triggeredBy = 'scheduler',
    ) {}
}
