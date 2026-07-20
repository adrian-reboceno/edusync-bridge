<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Http\Middleware;

use Auth\User\Domain\Ports\TokenServiceContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Rbac2Middleware
{
    public function __construct(
        private readonly TokenServiceContract $tokens,
    ) {}

    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            return $this->unauthorized('Missing bearer token.');
        }

        try {
            $payload = $this->tokens->verifyAccessToken($token);
        } catch (Throwable) {
            return $this->unauthorized('Invalid or expired token.');
        }

        $jti = (string) ($payload['jti'] ?? '');

        if ($jti === '' || $this->tokens->isBlacklisted($jti)) {
            return $this->unauthorized('Token has been revoked.');
        }

        if ($permission !== null && ! in_array($permission, $payload['permissions'] ?? [], true)) {
            return response()->json([
                'errors' => [[
                    'code' => 'FORBIDDEN',
                    'message' => "Missing required permission: {$permission}.",
                    'field' => null,
                ]],
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('auth_user_id', $payload['sub'] ?? null);
        $request->attributes->set('auth_role_id', $payload['role_id'] ?? null);
        $request->attributes->set('auth_session_id', $payload['session_id'] ?? null);
        $request->attributes->set('auth_permissions', $payload['permissions'] ?? []);
        $request->attributes->set('auth_jti', $jti);

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'errors' => [[
                'code' => 'UNAUTHENTICATED',
                'message' => $message,
                'field' => null,
            ]],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
