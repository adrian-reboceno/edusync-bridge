<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Services;

use Auth\User\Domain\Ports\TokenServiceContract;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Shared\Domain\ValueObjects\Uuid;
use UnexpectedValueException;

final class SanctumTokenService implements TokenServiceContract
{
    private const string BLACKLIST_PREFIX = 'auth:jwt:blacklist:';

    private const int REFRESH_TOKEN_MAX_TTL_DAYS = 30;

    private readonly string $secret;

    public function __construct(?string $secret = null)
    {
        $this->secret = $secret ?? (string) config('app.key');
    }

    public function issueAccessToken(Uuid $userId, Uuid $roleId, array $permissions, Uuid $sessionId): string
    {
        $now = time();

        return $this->encode([
            'sub' => $userId->toString(),
            'role_id' => $roleId->toString(),
            'permissions' => $permissions,
            'session_id' => $sessionId->toString(),
            'jti' => (string) Str::uuid(),
            'iat' => $now,
            'exp' => $now + (15 * 60),
        ]);
    }

    public function issueRefreshToken(Uuid $sessionId): string
    {
        $now = time();

        return $this->encode([
            'session_id' => $sessionId->toString(),
            'jti' => (string) Str::uuid(),
            'iat' => $now,
            'exp' => $now + (self::REFRESH_TOKEN_MAX_TTL_DAYS * 86400),
            'type' => 'refresh',
        ]);
    }

    public function verifyAccessToken(string $token): array
    {
        return $this->decode($token);
    }

    public function verifyRefreshToken(string $token): array
    {
        $payload = $this->decode($token);

        if (($payload['type'] ?? null) !== 'refresh') {
            throw new UnexpectedValueException('Token is not a refresh token.');
        }

        return $payload;
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function blacklist(string $jti, int $ttlSeconds): void
    {
        if ($jti === '' || $ttlSeconds <= 0) {
            return;
        }

        Redis::setex(self::BLACKLIST_PREFIX.$jti, $ttlSeconds, '1');
    }

    public function isBlacklisted(string $jti): bool
    {
        if ($jti === '') {
            return true;
        }

        return (bool) Redis::exists(self::BLACKLIST_PREFIX.$jti);
    }

    private function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $this->secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function decode(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw new UnexpectedValueException('Malformed JWT.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSignature = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->secret, true);

        if (! hash_equals($expectedSignature, self::base64UrlDecode($signatureB64))) {
            throw new UnexpectedValueException('Invalid JWT signature.');
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);

        if (! is_array($payload)) {
            throw new UnexpectedValueException('Invalid JWT payload.');
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            throw new UnexpectedValueException('Token has expired.');
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $padded = str_pad($data, strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + (4 - strlen($data) % 4), '=');

        return base64_decode(strtr($padded, '-_', '+/')) ?: '';
    }
}
