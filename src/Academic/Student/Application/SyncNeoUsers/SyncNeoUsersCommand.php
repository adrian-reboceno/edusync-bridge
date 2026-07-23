<?php

declare(strict_types=1);

namespace Academic\Student\Application\SyncNeoUsers;

final readonly class SyncNeoUsersCommand
{
    public function __construct(
        public ?int   $organizationId = null,
        public bool   $includeArchived = false,
        public string $triggeredBy = 'scheduler',
    ) {}
}
