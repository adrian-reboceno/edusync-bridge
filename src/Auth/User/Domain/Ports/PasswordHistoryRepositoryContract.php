<?php

declare(strict_types=1);

namespace Auth\User\Domain\Ports;

use Auth\User\Domain\ValueObjects\HashedPassword;
use Shared\Domain\ValueObjects\Uuid;

interface PasswordHistoryRepositoryContract
{
    /**
     * @return HashedPassword[]
     */
    public function getRecentByUser(Uuid $userId, int $limit = 5): array;

    public function record(Uuid $userId, HashedPassword $hash): void;
}
