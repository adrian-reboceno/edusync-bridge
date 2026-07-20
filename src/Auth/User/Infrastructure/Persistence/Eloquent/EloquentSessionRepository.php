<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Persistence\Eloquent;

use Auth\User\Domain\Entities\UserSession;
use Auth\User\Domain\Ports\SessionRepositoryContract;
use Shared\Domain\ValueObjects\Uuid;

final class EloquentSessionRepository implements SessionRepositoryContract
{
    public function findById(Uuid $id): ?UserSession
    {
        $model = EloquentUserSessionModel::query()->find($id->toString());

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findActiveByUser(Uuid $userId, string $clientType): ?UserSession
    {
        $model = EloquentUserSessionModel::query()
            ->where('user_id', $userId->toString())
            ->where('client_type', $clientType)
            ->whereNull('revoked_at')
            ->orderByDesc('last_activity_at')
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByRefreshTokenHash(string $refreshTokenHash): ?UserSession
    {
        $model = EloquentUserSessionModel::query()
            ->where('refresh_token_hash', $refreshTokenHash)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function save(UserSession $session): void
    {
        EloquentUserSessionModel::query()->updateOrCreate(
            ['id' => $session->getId()->toString()],
            [
                'user_id' => $session->getUserId()->toString(),
                'client_type' => $session->getClientType(),
                'access_token_hash' => $session->getAccessTokenHash(),
                'refresh_token_hash' => $session->getRefreshTokenHash(),
                'access_expires_at' => $session->getAccessExpiresAt(),
                'refresh_expires_at' => $session->getRefreshExpiresAt(),
                'active_role_id' => $session->getActiveRoleId()?->toString(),
                'last_activity_at' => $session->getLastActivityAt(),
                'ip_address' => $session->getIpAddress(),
                'user_agent' => $session->getUserAgent(),
                'revoked_at' => $session->getRevokedAt(),
            ],
        );
    }

    public function revoke(Uuid $sessionId): void
    {
        EloquentUserSessionModel::query()
            ->where('id', $sessionId->toString())
            ->update(['revoked_at' => now()]);
    }

    private function toDomain(EloquentUserSessionModel $model): UserSession
    {
        return UserSession::reconstitute(
            id: Uuid::fromString($model->id),
            userId: Uuid::fromString($model->user_id),
            clientType: $model->client_type,
            accessTokenHash: $model->access_token_hash,
            refreshTokenHash: $model->refresh_token_hash,
            accessExpiresAt: $model->access_expires_at->toDateTimeImmutable(),
            refreshExpiresAt: $model->refresh_expires_at->toDateTimeImmutable(),
            activeRoleId: $model->active_role_id !== null ? Uuid::fromString($model->active_role_id) : null,
            roleActivatedAt: $model->role_activated_at?->toDateTimeImmutable(),
            lastActivityAt: $model->last_activity_at->toDateTimeImmutable(),
            ipAddress: $model->ip_address,
            userAgent: $model->user_agent,
            revokedAt: $model->revoked_at?->toDateTimeImmutable(),
        );
    }
}
