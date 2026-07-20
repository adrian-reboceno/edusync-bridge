<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Persistence\Eloquent;

use Auth\User\Domain\Entities\User;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Auth\User\Domain\ValueObjects\Email;
use Auth\User\Domain\ValueObjects\HashedPassword;
use Auth\User\Domain\ValueObjects\UserStatus;
use Shared\Domain\ValueObjects\Uuid;

final class EloquentUserRepository implements UserRepositoryContract
{
    public function findById(Uuid $id): ?User
    {
        $model = EloquentUserModel::query()->find($id->toString());

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = EloquentUserModel::query()->where('email', $email->toString())->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function save(User $user): void
    {
        EloquentUserModel::query()->updateOrCreate(
            ['id' => $user->getId()->toString()],
            $this->toPersistence($user),
        );
    }

    public function existsByEmail(Email $email): bool
    {
        return EloquentUserModel::query()->where('email', $email->toString())->exists();
    }

    private function toDomain(EloquentUserModel $model): User
    {
        return User::reconstitute(
            id: Uuid::fromString($model->id),
            email: new Email($model->email),
            passwordHash: HashedPassword::fromHash($model->password_hash),
            firstName: $model->first_name,
            lastName: $model->last_name,
            phone: $model->phone,
            status: UserStatus::from($model->status),
            twoFactorEnabled: (bool) $model->two_factor_enabled,
            twoFactorSecret: $model->two_factor_secret,
            mustChangePassword: (bool) $model->must_change_password,
            passwordChangedAt: $model->password_changed_at?->toDateTimeImmutable(),
            failedLoginAttempts: (int) $model->failed_login_attempts,
            lockedUntil: $model->locked_until?->toDateTimeImmutable(),
            lastLoginAt: $model->last_login_at?->toDateTimeImmutable(),
            lastActivityAt: $model->last_activity_at?->toDateTimeImmutable(),
            emailVerifiedAt: $model->email_verified_at?->toDateTimeImmutable(),
            createdBy: $model->created_by !== null ? Uuid::fromString($model->created_by) : null,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    private function toPersistence(User $user): array
    {
        return [
            'email' => $user->getEmail()->toString(),
            'password_hash' => $user->getPasswordHash()->toString(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'status' => $user->getStatus()->value,
            'two_factor_enabled' => $user->isTwoFactorEnabled(),
            'two_factor_secret' => $user->getTwoFactorSecret(),
            'must_change_password' => $user->mustChangePassword(),
            'failed_login_attempts' => $user->getFailedLoginAttempts(),
            'locked_until' => $user->getLockedUntil(),
            'last_login_at' => $user->getLastLoginAt(),
            'created_by' => $user->getCreatedBy()?->toString(),
            'updated_at' => $user->getUpdatedAt(),
        ];
    }
}
