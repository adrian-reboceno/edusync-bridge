<?php

declare(strict_types=1);

namespace Shared\Domain\Contracts;

interface EventBusContract
{
    public function dispatch(DomainEvent $event): void;
}
