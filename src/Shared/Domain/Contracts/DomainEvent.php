<?php

declare(strict_types=1);

namespace Shared\Domain\Contracts;

use DateTimeImmutable;

interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;

    public function name(): string;

    public function payload(): array;
}
