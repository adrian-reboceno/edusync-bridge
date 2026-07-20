<?php

declare(strict_types=1);

namespace Auth\User\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class AccessToken
{
    private array $payload;

    public function __construct(
        private string $jwt,
    ) {
        $segments = explode('.', $this->jwt);

        if (count($segments) !== 3) {
            throw new InvalidArgumentException('Malformed JWT: expected 3 segments');
        }

        $decoded = json_decode(self::base64UrlDecode($segments[1]), true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Malformed JWT payload');
        }

        $this->payload = $decoded;
    }

    public function getJti(): string
    {
        return (string) ($this->payload['jti'] ?? '');
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function isExpired(): bool
    {
        $exp = $this->payload['exp'] ?? null;

        if ($exp === null) {
            return true;
        }

        return (int) $exp < time();
    }

    public function toString(): string
    {
        return $this->jwt;
    }

    private static function base64UrlDecode(string $data): string
    {
        $padded = str_pad($data, strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + (4 - strlen($data) % 4), '=');

        return base64_decode(strtr($padded, '-_', '+/')) ?: '';
    }
}
