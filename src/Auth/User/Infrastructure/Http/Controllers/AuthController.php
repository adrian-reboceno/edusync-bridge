<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Http\Controllers;

use Auth\Role\Application\SwitchRole\SwitchRoleCommand;
use Auth\Role\Application\SwitchRole\SwitchRoleUseCase;
use Auth\User\Application\Enable2fa\Enable2faCommand;
use Auth\User\Application\Enable2fa\Enable2faUseCase;
use Auth\User\Application\GetMe\GetMeQuery;
use Auth\User\Application\GetMe\GetMeUseCase;
use Auth\User\Application\Login\LoginCommand;
use Auth\User\Application\Login\LoginUseCase;
use Auth\User\Application\Logout\LogoutCommand;
use Auth\User\Application\Logout\LogoutUseCase;
use Auth\User\Application\RefreshToken\RefreshTokenCommand;
use Auth\User\Application\RefreshToken\RefreshTokenUseCase;
use Auth\User\Application\Setup2fa\Setup2faCommand;
use Auth\User\Application\Setup2fa\Setup2faUseCase;
use Auth\User\Application\UnlockUser\UnlockUserCommand;
use Auth\User\Application\UnlockUser\UnlockUserUseCase;
use Auth\User\Infrastructure\Http\Requests\LoginRequest;
use Auth\User\Infrastructure\Http\Requests\RefreshTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * @tags Auth
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly RefreshTokenUseCase $refreshTokenUseCase,
        private readonly LogoutUseCase $logoutUseCase,
        private readonly SwitchRoleUseCase $switchRoleUseCase,
        private readonly UnlockUserUseCase $unlockUserUseCase,
        private readonly Setup2faUseCase $setup2faUseCase,
        private readonly Enable2faUseCase $enable2faUseCase,
        private readonly GetMeUseCase $getMeUseCase,
    ) {}

    /**
     * Iniciar sesión
     *
     * Autentica al usuario con email y contraseña.
     * Si el rol requiere 2FA y no está configurado, retorna requires_two_factor_setup=true.
     * Si 2FA está configurado, retorna requires_two_factor=true y se debe enviar totp_code.
     *
     * @bodyParam client_type string optional Tipo de cliente. WEB = refresh token 7 días, MOBILE = 30 días. Default: WEB.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginUseCase->execute(new LoginCommand(
            email: $request->string('email')->toString(),
            password: (string) $request->input('password'),
            ipAddress: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
            clientType: (string) $request->input('client_type', 'WEB'),
            totpCode: $request->input('totp_code'),
            selectedRoleId: $request->input('selected_role_id'),
        ));

        return response()->json([
            'data' => [
                'access_token' => $result->accessToken,
                'refresh_token' => $result->refreshToken,
                'user' => $result->user,
                'active_role' => $result->activeRole,
                'requires_role_selection' => $result->requiresRoleSelection,
                'available_roles' => $result->availableRoles,
                'requires_two_factor' => $result->requiresTwoFactor,
                'requires_two_factor_setup' => $result->requiresTwoFactorSetup,
                'must_change_password' => $result->mustChangePassword,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Verificar código TOTP (2FA)
     *
     * Alias de login con totp_code incluido. Completa la autenticación de dos factores.
     */
    public function verify2fa(LoginRequest $request): JsonResponse
    {
        return $this->login($request);
    }

    /**
     * Renovar access token
     *
     * Genera un nuevo par de tokens usando el refresh token.
     * El refresh token anterior queda en blacklist (refresh rotation).
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->refreshTokenUseCase->execute(new RefreshTokenCommand(
            refreshToken: (string) $request->input('refresh_token'),
        ));

        return response()->json([
            'data' => [
                'access_token' => $result->accessToken,
                'refresh_token' => $result->refreshToken,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Cerrar sesión
     *
     * Revoca la sesión activa y agrega el access token a la blacklist de Redis.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->logoutUseCase->execute(new LogoutCommand(
            sessionId: (string) $request->attributes->get('auth_session_id'),
            accessToken: (string) $request->attributes->get('auth_jti'),
            userId: (string) $request->attributes->get('auth_user_id'),
            ipAddress: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
        ));

        return response()->json([
            'data' => ['message' => 'Logged out successfully.'],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Cambiar rol activo (DSoD)
     *
     * Cambia el rol activo en la sesión actual.
     * Emite un nuevo access token con los permisos del rol seleccionado.
     */
    public function switchRole(Request $request): JsonResponse
    {
        $result = $this->switchRoleUseCase->execute(new SwitchRoleCommand(
            sessionId: (string) $request->attributes->get('auth_session_id'),
            currentAccessToken: (string) $request->attributes->get('auth_jti'),
            userId: (string) $request->attributes->get('auth_user_id'),
            targetRoleId: (string) $request->input('role_id'),
            ipAddress: (string) $request->ip(),
        ));

        return response()->json([
            'data' => [
                'access_token' => $result->accessToken,
                'active_role' => $result->activeRole,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Desbloquear cuenta de usuario
     *
     * Desbloquea una cuenta bloqueada por intentos fallidos.
     * Requiere permiso: auth.users.unlock
     */
    public function unlock(Request $request, string $id): JsonResponse
    {
        $this->unlockUserUseCase->execute(new UnlockUserCommand(
            targetUserId: $id,
            unlockedBy: (string) $request->attributes->get('auth_user_id'),
            actorRoleId: (string) $request->attributes->get('auth_role_id'),
            ipAddress: (string) $request->ip(),
        ));

        return response()->json([
            'data' => ['message' => 'User unlocked successfully.'],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Configurar autenticación de dos factores
     *
     * Genera un secreto TOTP y retorna el QR en base64 (SVG)
     * para escanear con Google Authenticator o similar.
     */
    public function setup2fa(Request $request): JsonResponse
    {
        $result = $this->setup2faUseCase->execute(new Setup2faCommand(
            email: (string) $request->input('email'),
        ));

        return response()->json([
            'data' => [
                'secret' => $result->secret,
                'qr_code_url' => $result->qrCodeUrl,
                'qr_svg' => $result->qrSvg,
                'instructions' => 'Escanea el QR con Google Authenticator o ingresa el secreto manualmente.',
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Activar autenticación de dos factores
     *
     * Verifica el código TOTP con el secreto provisional y activa 2FA
     * en la cuenta del usuario. A partir de este momento el login requiere código.
     */
    public function enable2fa(Request $request): JsonResponse
    {
        $this->enable2faUseCase->execute(new Enable2faCommand(
            email: (string) $request->input('email'),
            secret: (string) $request->input('secret'),
            totpCode: (string) $request->input('totp_code'),
            ipAddress: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
        ));

        return response()->json([
            'data' => ['message' => '2FA activado correctamente. Ya puedes iniciar sesión con tu código TOTP.'],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Obtener datos del usuario autenticado
     *
     * Retorna el perfil, rol activo y permisos del usuario autenticado
     * a partir del JWT sin consultar la BD.
     */
    public function me(Request $request): JsonResponse
    {
        $result = $this->getMeUseCase->execute(new GetMeQuery(
            userId: (string) $request->attributes->get('auth_user_id'),
            roleId: (string) $request->attributes->get('auth_role_id'),
            permissions: (array) $request->attributes->get('auth_permissions', []),
            sessionId: (string) $request->attributes->get('auth_session_id'),
        ));

        return response()->json([
            'data' => [
                'user' => $result->user,
                'active_role_id' => $result->activeRoleId,
                'permissions' => $result->permissions,
                'session_id' => $result->sessionId,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }
}
