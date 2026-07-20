<?php

declare(strict_types=1);

namespace Shared\Domain\Contracts;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;

    public function name(): string;

    public function payload(): array;
}
