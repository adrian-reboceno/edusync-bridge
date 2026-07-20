<?php

declare(strict_types=1);

namespace Auth\User\Application\GetMe;

use Auth\User\Domain\Ports\UserRepositoryContract;
use Shared\Domain\ValueObjects\Uuid;

final readonly class GetMeUseCase
{
    public function __construct(
        private UserRepositoryContract $users,
    ) {}

    public function execute(GetMeQuery $query): GetMeResult
    {
        $user = $this->users->findById(Uuid::fromString($query->userId));

        if ($user === null) {
            throw new \RuntimeException('User not found.');
        }

        return new GetMeResult(
            user: [
                'id' => $user->getId()->toString(),
                'email' => $user->getEmail()->toString(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'status' => $user->getStatus()->value,
                'two_factor_enabled' => $user->isTwoFactorEnabled(),
                'must_change_password' => $user->mustChangePassword(),
            ],
            activeRoleId: $query->roleId,
            permissions: $query->permissions,
            sessionId: $query->sessionId,
        );
    }
}
