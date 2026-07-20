<?php

declare(strict_types=1);

namespace Auth\User\Application\ChangePassword;

use Auth\User\Domain\Exceptions\InvalidCredentialsException;
use Auth\User\Domain\Exceptions\PasswordReusedException;
use Auth\User\Domain\Ports\PasswordHistoryRepositoryContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Auth\User\Domain\ValueObjects\Password;
use Shared\Domain\ValueObjects\Uuid;

final readonly class ChangePasswordUseCase
{
    private const int HISTORY_LIMIT = 5;

    public function __construct(
        private UserRepositoryContract $users,
        private PasswordHistoryRepositoryContract $passwordHistories,
    ) {}

    public function execute(ChangePasswordCommand $command): void
    {
        $userId = Uuid::fromString($command->userId);
        $user = $this->users->findById($userId);

        if ($user === null || ! $user->verifyPassword(new Password($command->currentPassword))) {
            throw new InvalidCredentialsException();
        }

        $newPassword = new Password($command->newPassword);
        $recentHashes = $this->passwordHistories->getRecentByUser($userId, self::HISTORY_LIMIT);

        foreach ($recentHashes as $recentHash) {
            if ($recentHash->verify($newPassword)) {
                throw new PasswordReusedException(self::HISTORY_LIMIT);
            }
        }

        $newHash = $newPassword->hash();

        $user->changePassword($newHash);
        $this->users->save($user);

        $this->passwordHistories->record($userId, $newHash);
    }
}
