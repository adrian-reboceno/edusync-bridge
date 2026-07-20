<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Shared\Domain\Contracts\DomainEvent;
use Shared\Domain\Contracts\EventBusContract;

final class LaravelEventBus implements EventBusContract
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event->name(), $event->payload());
    }
}
