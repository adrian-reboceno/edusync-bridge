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
use OpenApi\Annotations as OA;

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
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Auth"},
     *     summary="Iniciar sesión",
     *     description="Autentica al usuario. Si el rol requiere 2FA y no está configurado retorna requires_two_factor_setup=true. Si ya está configurado retorna requires_two_factor=true.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="admin@edusync.edu"),
     *             @OA\Property(property="password", type="string", minLength=8, example="Admin@2024!"),
     *             @OA\Property(property="totp_code", type="string", nullable=true, minLength=6, maxLength=6, example="123456"),
     *             @OA\Property(property="client_type", type="string", nullable=true, enum={"WEB","MOBILE"}, default="WEB", description="WEB=refresh 7 días, MOBILE=30 días"),
     *             @OA\Property(property="selected_role_id", type="string", format="uuid", nullable=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", nullable=true),
     *                 @OA\Property(property="refresh_token", type="string", nullable=true),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="first_name", type="string"),
     *                     @OA\Property(property="last_name", type="string"),
     *                     @OA\Property(property="status", type="string")
     *                 ),
     *                 @OA\Property(property="active_role", type="object", nullable=true,
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="super-admin"),
     *                     @OA\Property(property="display_name", type="string"),
     *                     @OA\Property(property="hierarchy_level", type="integer")
     *                 ),
     *              @OA\Property(property="permissions", type="array",        
     *                      description="Permisos del rol activo",
     *                      @OA\Items(type="string", example="reports.sync.view")
     *                 ),
     *                 @OA\Property(property="requires_role_selection", type="boolean"),
     *                 @OA\Property(property="available_roles", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="requires_two_factor", type="boolean"),
     *                 @OA\Property(property="requires_two_factor_setup", type="boolean"),
     *                 @OA\Property(property="must_change_password", type="boolean")
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Credenciales inválidas"),
     *     @OA\Response(response=423, description="Cuenta bloqueada"),
     *     @OA\Response(response=429, description="Demasiados intentos")
     * )
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
                'permissions'               => $result->permissions,
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
     * @OA\Post(
     *     path="/api/v1/auth/2fa/verify",
     *     tags={"Auth"},
     *     summary="Verificar código TOTP (2FA)",
     *     description="Alias de login con totp_code incluido. Completa la autenticación de dos factores.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password","totp_code"},
     *
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", minLength=8),
     *             @OA\Property(property="totp_code", type="string", minLength=6, maxLength=6, example="123456"),
     *             @OA\Property(property="client_type", type="string", nullable=true, enum={"WEB","MOBILE"}, default="WEB"),
     *             @OA\Property(property="selected_role_id", type="string", format="uuid", nullable=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Login exitoso"),
     *     @OA\Response(response=401, description="Credenciales o código TOTP inválidos"),
     *     @OA\Response(response=423, description="Cuenta bloqueada"),
     *     @OA\Response(response=429, description="Demasiados intentos")
     * )
     */
    public function verify2fa(LoginRequest $request): JsonResponse
    {
        return $this->login($request);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Auth"},
     *     summary="Renovar access token",
     *     description="Genera nuevos tokens usando el refresh token. El token anterior queda en blacklist (refresh rotation).",
     *
     *     @OA\RequestBody(required=true,
     *
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Tokens renovados",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="refresh_token", type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Refresh token inválido o expirado")
     * )
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
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Auth"},
     *     summary="Cerrar sesión",
     *     description="Revoca la sesión activa y agrega el access token a la blacklist de Redis.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Sesión cerrada",
     *
     *         @OA\JsonContent(@OA\Property(property="data", type="object",
     *
     *             @OA\Property(property="message", type="string", example="Logged out successfully.")
     *         ))
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     * @OA\Post(
     *     path="/api/v1/auth/session/role",
     *     tags={"Auth"},
     *     summary="Cambiar rol activo (DSoD)",
     *     description="Cambia el rol activo en la sesión actual. Emite un nuevo access token con los permisos del rol seleccionado.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(required=true,
     *
     *         @OA\JsonContent(required={"role_id"},
     *
     *             @OA\Property(property="role_id", type="string", format="uuid")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Rol cambiado, nuevo access token emitido",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="active_role", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Rol no permitido para este usuario (DSoD)")
     * )
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
     * @OA\Post(
     *     path="/api/v1/users/{id}/unlock",
     *     tags={"Auth"},
     *     summary="Desbloquear cuenta de usuario",
     *     description="Desbloquea una cuenta bloqueada por intentos fallidos. Requiere permiso: auth.users.unlock",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(response=200, description="Cuenta desbloqueada",
     *
     *         @OA\JsonContent(@OA\Property(property="data", type="object",
     *
     *             @OA\Property(property="message", type="string", example="User unlocked successfully.")
     *         ))
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso auth.users.unlock")
     * )
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
     * @OA\Post(
     *     path="/api/v1/auth/2fa/setup",
     *     tags={"Auth"},
     *     summary="Configurar autenticación de dos factores",
     *     description="Genera un secreto TOTP y retorna el QR en base64 (SVG) para escanear con Google Authenticator.",
     *
     *     @OA\RequestBody(required=true,
     *
     *         @OA\JsonContent(required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="QR y secreto generados",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="secret", type="string", example="JBSWY3DPEHPK3PXP"),
     *                 @OA\Property(property="qr_code_url", type="string"),
     *                 @OA\Property(property="qr_svg", type="string", description="SVG en base64"),
     *                 @OA\Property(property="instructions", type="string")
     *             )
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/auth/2fa/enable",
     *     tags={"Auth"},
     *     summary="Activar autenticación de dos factores",
     *     description="Verifica el código TOTP con el secreto provisional y activa 2FA en la cuenta del usuario. A partir de este momento el login requiere código.",
     *
     *     @OA\RequestBody(required=true,
     *
     *         @OA\JsonContent(required={"email","secret","totp_code"},
     *
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="secret", type="string"),
     *             @OA\Property(property="totp_code", type="string", minLength=6, maxLength=6)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="2FA activado",
     *
     *         @OA\JsonContent(@OA\Property(property="data", type="object",
     *
     *             @OA\Property(property="message", type="string", example="2FA activado correctamente. Ya puedes iniciar sesión con tu código TOTP.")
     *         ))
     *     ),
     *
     *     @OA\Response(response=401, description="Código TOTP inválido")
     * )
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
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Auth"},
     *     summary="Datos del usuario autenticado",
     *     description="Retorna el perfil, rol activo y permisos del usuario autenticado a partir del JWT sin consultar la BD.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Perfil del usuario",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="active_role_id", type="string", format="uuid"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="session_id", type="string", format="uuid")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
