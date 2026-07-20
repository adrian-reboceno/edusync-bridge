<?php

declare(strict_types=1);

namespace Shared\Domain\Contracts;

use Shared\Domain\ValueObjects\Uuid;

interface RepositoryContract
{
    public function findById(Uuid $id): ?object;

    public function save(object $entity): void;

    public function delete(Uuid $id): void;
}
