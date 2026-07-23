<?php

declare(strict_types=1);

namespace Academic\Student\Application\DTOs;

use DateTimeImmutable;

final readonly class NeoUserSessionDTO
{
    public function __construct(
        public int     $neoSessionId,
        public int     $neoUserId,
        public ?string $sisId,
        public string  $loginAt,
        public ?string $logoutAt,
        public ?string $ipAddress,
    ) {}

    public static function fromApiResponse(array $data, ?string $sisId = null): self
    {
        return new self(
            neoSessionId: (int) $data['id'],
            neoUserId:    (int) $data['user_id'],
            sisId:        $sisId,
            loginAt:      $data['login_at'],
            logoutAt:     $data['logout_at'] ?? null,
            ipAddress:    $data['ip_address'] ?? null,
        );
    }

    /**
     * Duracion en segundos cuando logout_at esta disponible; null si la sesion sigue abierta.
     */
    public function durationSeconds(): ?int
    {
        if ($this->logoutAt === null) {
            return null;
        }

        $login = new DateTimeImmutable($this->loginAt);
        $logout = new DateTimeImmutable($this->logoutAt);

        return max(0, $logout->getTimestamp() - $login->getTimestamp());
    }
}
