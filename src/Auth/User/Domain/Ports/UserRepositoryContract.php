<?php

declare(strict_types=1);

namespace Auth\User\Domain\Ports;

use Auth\User\Domain\Entities\User;
use Auth\User\Domain\ValueObjects\Email;
use Shared\Domain\ValueObjects\Uuid;

interface UserRepositoryContract
{
    public function findById(Uuid $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function save(User $user): void;

    public function existsByEmail(Email $email): bool;
}
