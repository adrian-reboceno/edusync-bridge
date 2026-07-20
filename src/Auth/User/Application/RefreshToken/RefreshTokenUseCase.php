<?php

declare(strict_types=1);

namespace Auth\User\Application\RefreshToken;

use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\User\Domain\Exceptions\InvalidCredentialsException;
use Auth\User\Domain\Ports\SessionRepositoryContract;
use Auth\User\Domain\Ports\TokenServiceContract;
use DateTimeImmutable;
use Shared\Domain\ValueObjects\Uuid;
use Throwable;

final readonly class RefreshTokenUseCase
{
    private const int ACCESS_TOKEN_TTL_MINUTES = 15;

    public function __construct(
        private SessionRepositoryContract $sessions,
        private TokenServiceContract $tokens,
        private RoleRepositoryContract $roles,
    ) {}

    public function execute(RefreshTokenCommand $command): RefreshTokenResult
    {
        try {
            $payload = $this->tokens->verifyRefreshToken($command->refreshToken);
        } catch (Throwable) {
            throw new InvalidCredentialsException();
        }

        $jti = (string) ($payload['jti'] ?? '');
        $exp = (int) ($payload['exp'] ?? 0);

        if ($jti === '' || $this->tokens->isBlacklisted($jti)) {
            throw new InvalidCredentialsException();
        }

        $session = $this->sessions->findByRefreshTokenHash($this->tokens->hash($command->refreshToken));

        if ($session === null || $session->isRevoked() || $session->isRefreshExpired()) {
            throw new InvalidCredentialsException();
        }

        $ttlRemaining = max(0, $exp - time());
        $this->tokens->blacklist($jti, $ttlRemaining);

        $activeRoleId = $session->getActiveRoleId();
        $permissions = $activeRoleId !== null
            ? $this->roles->getPermissionsForRole($activeRoleId)
            : [];

        $newAccessToken = $this->tokens->issueAccessToken(
            $session->getUserId(),
            $activeRoleId ?? Uuid::generate(),
            $permissions,
            $session->getId(),
        );
        $newRefreshToken = $this->tokens->issueRefreshToken($session->getId());

        $refreshTtlSeconds = $session->getRefreshExpiresAt()->getTimestamp() - time();

        $session->rotateTokens(
            accessTokenHash: $this->tokens->hash($newAccessToken),
            refreshTokenHash: $this->tokens->hash($newRefreshToken),
            accessExpiresAt: (new DateTimeImmutable())->modify('+'.self::ACCESS_TOKEN_TTL_MINUTES.' minutes'),
            refreshExpiresAt: (new DateTimeImmutable())->modify("+{$refreshTtlSeconds} seconds"),
        );

        $this->sessions->save($session);

        return new RefreshTokenResult($newAccessToken, $newRefreshToken);
    }
}
