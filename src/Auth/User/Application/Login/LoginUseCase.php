<?php

declare(strict_types=1);

namespace Auth\User\Application\Login;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\Role\Domain\Entities\Role;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\User\Domain\Entities\User;
use Auth\User\Domain\Entities\UserSession;
use Auth\User\Domain\Events\LoginSucceeded;
use Auth\User\Domain\Exceptions\InvalidCredentialsException;
use Auth\User\Domain\Ports\SessionRepositoryContract;
use Auth\User\Domain\Ports\TokenServiceContract;
use Auth\User\Domain\Ports\TotpServiceContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Auth\User\Domain\ValueObjects\Email;
use Auth\User\Domain\ValueObjects\Password;
use DateTimeImmutable;
use Shared\Domain\Contracts\EventBusContract;
use Shared\Domain\ValueObjects\Uuid;

final readonly class LoginUseCase
{
    private const int MAX_FAILED_ATTEMPTS = 5;

    private const int BASE_LOCK_MINUTES = 15;

    private const int ACCESS_TOKEN_TTL_MINUTES = 15;

    private const int REFRESH_TOKEN_TTL_DAYS_WEB = 7;

    private const int REFRESH_TOKEN_TTL_DAYS_MOBILE = 30;

    public function __construct(
        private UserRepositoryContract $users,
        private SessionRepositoryContract $sessions,
        private TokenServiceContract $tokens,
        private RoleRepositoryContract $roles,
        private AuditLogRepositoryContract $auditLogs,
        private TotpServiceContract $totp,
        private EventBusContract $events,
    ) {}

    public function execute(LoginCommand $command): LoginResult
    {
        $user = $this->users->findByEmail(new Email($command->email));

        if ($user === null) {
            throw new InvalidCredentialsException();
        }

        $user->assertNotLocked();

        if (! $user->verifyPassword(new Password($command->password))) {
            $this->handleFailedAttempt($user, $command->ipAddress);

            throw new InvalidCredentialsException();
        }

        if (! $user->getStatus()->canLogin()) {
            throw new InvalidCredentialsException();
        }

        $roles = $this->roles->getByUser($user->getId());
        $roleRequiresTwoFactor = $this->anyRoleRequiresTwoFactor($roles);

        // Caso A: rol requiere 2FA pero el usuario NO lo tiene configurado
        if ($user->requiresTwoFactorSetup($roleRequiresTwoFactor)) {
            return $this->pendingTwoFactorSetupResult($user);
        }

        // Caso B: usuario ya tiene 2FA configurado → verificar código TOTP
        if ($user->requiresTwoFactorVerification()) {
            if ($command->totpCode === null) {
                return $this->pendingTwoFactorResult($user);
            }
            $secret = $user->getTwoFactorSecret();
            if ($secret === null || ! $this->totp->verify($secret, $command->totpCode)) {
                throw new InvalidCredentialsException();
            }
        }

        $activeRole = $this->resolveActiveRole($roles, $command->selectedRoleId);

        if ($activeRole === null && count($roles) > 1) {
            return $this->pendingRoleSelectionResult($user, $roles);
        }

        $user->recordLogin();
        $this->users->save($user);
        $this->releaseEvents($user);

        $session = $this->createSession($user, $activeRole, $command);

        $this->auditLogs->append(AuditLog::record(
            userId: $user->getId(),
            userEmail: $user->getEmail()->toString(),
            userRole: $activeRole?->getName()->toString() ?? '',
            module: 'AUTH',
            action: 'LOGIN_SUCCEEDED',
            ipAddress: $command->ipAddress,
            userAgent: $command->userAgent,
        ));

        $this->events->dispatch(new LoginSucceeded($user->getId(), $session['sessionId'], $command->ipAddress));

        return new LoginResult(
            accessToken: $session['accessToken'],
            refreshToken: $session['refreshToken'],
            user: $this->userToArray($user),
            activeRole: $activeRole !== null ? $this->roleToArray($activeRole) : null,
            requiresRoleSelection: false,
            availableRoles: [],
            requiresTwoFactor: false,
            requiresTwoFactorSetup: false,
            mustChangePassword: $user->mustChangePassword() || $user->checkPasswordExpiry(),
        );
    }

    private function handleFailedAttempt(User $user, string $ipAddress): void
    {
        $user->incrementFailedAttempts();

        $priorLocksToday = $this->auditLogs->countTodayByUserAndAction($user->getId(), 'ACCOUNT_LOCKED');
        $lockDuration = self::BASE_LOCK_MINUTES * (2 ** $priorLocksToday);

        $user->checkLockThreshold(self::MAX_FAILED_ATTEMPTS, $lockDuration);
        $this->users->save($user);
        $this->releaseEvents($user);

        $this->auditLogs->append(AuditLog::record(
            userId: $user->getId(),
            userEmail: $user->getEmail()->toString(),
            userRole: '',
            module: 'AUTH',
            action: $user->getStatus()->isLocked() ? 'ACCOUNT_LOCKED' : 'LOGIN_FAILED',
            ipAddress: $ipAddress,
            status: 'FAILURE',
        ));
    }

    /**
     * @param  Role[]  $roles
     */
    private function anyRoleRequiresTwoFactor(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($role->isTwoFactorRequired()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Role[]  $roles
     */
    private function resolveActiveRole(array $roles, ?string $selectedRoleId): ?Role
    {
        if (count($roles) === 0) {
            return null;
        }

        if (count($roles) === 1) {
            return $roles[0];
        }

        if ($selectedRoleId === null) {
            return null;
        }

        $selected = Uuid::fromString($selectedRoleId);

        foreach ($roles as $role) {
            if ($role->getId()->equals($selected)) {
                return $role;
            }
        }

        throw new InvalidCredentialsException();
    }

    /**
     * @return array{accessToken: string, refreshToken: string, sessionId: Uuid}
     */
    private function createSession(User $user, ?Role $activeRole, LoginCommand $command): array
    {
        $sessionId = Uuid::generate();
        $permissions = $activeRole !== null
            ? $this->roles->getPermissionsForRole($activeRole->getId())
            : [];

        $accessToken = $this->tokens->issueAccessToken(
            $user->getId(),
            $activeRole?->getId() ?? Uuid::generate(),
            $permissions,
            $sessionId,
        );
        $refreshToken = $this->tokens->issueRefreshToken($sessionId);

        $refreshTtlDays = $command->clientType === 'MOBILE'
            ? self::REFRESH_TOKEN_TTL_DAYS_MOBILE
            : self::REFRESH_TOKEN_TTL_DAYS_WEB;

        $session = UserSession::create(
            userId: $user->getId(),
            clientType: $command->clientType,
            accessTokenHash: $this->tokens->hash($accessToken),
            refreshTokenHash: $this->tokens->hash($refreshToken),
            accessExpiresAt: (new DateTimeImmutable())->modify('+'.self::ACCESS_TOKEN_TTL_MINUTES.' minutes'),
            refreshExpiresAt: (new DateTimeImmutable())->modify("+{$refreshTtlDays} days"),
            ipAddress: $command->ipAddress,
            userAgent: $command->userAgent,
            activeRoleId: $activeRole?->getId(),
            id: $sessionId,
        );

        $this->sessions->save($session);

        return ['accessToken' => $accessToken, 'refreshToken' => $refreshToken, 'sessionId' => $sessionId];
    }

    private function releaseEvents(User $user): void
    {
        foreach ($user->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }
    }

    private function pendingTwoFactorResult(User $user): LoginResult
    {
        return new LoginResult(
            accessToken: null,
            refreshToken: null,
            user: $this->userToArray($user),
            activeRole: null,
            requiresRoleSelection: false,
            availableRoles: [],
            requiresTwoFactor: true,
            requiresTwoFactorSetup: false,
            mustChangePassword: $user->mustChangePassword() || $user->checkPasswordExpiry(),
        );
    }

    /**
     * @param  Role[]  $roles
     */
    private function pendingRoleSelectionResult(User $user, array $roles): LoginResult
    {
        return new LoginResult(
            accessToken: null,
            refreshToken: null,
            user: $this->userToArray($user),
            activeRole: null,
            requiresRoleSelection: true,
            availableRoles: array_map($this->roleToArray(...), $roles),
            requiresTwoFactor: false,
            requiresTwoFactorSetup: false,
            mustChangePassword: $user->mustChangePassword() || $user->checkPasswordExpiry(),
        );
    }

    private function userToArray(User $user): array
    {
        return [
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail()->toString(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'status' => $user->getStatus()->value,
        ];
    }

    private function roleToArray(Role $role): array
    {
        return [
            'id' => $role->getId()->toString(),
            'name' => $role->getName()->toString(),
            'display_name' => $role->getDisplayName(),
            'hierarchy_level' => $role->getHierarchyLevel()->toInt(),
        ];
    }

    private function pendingTwoFactorSetupResult(User $user): LoginResult
    {
        return new LoginResult(
            accessToken: null,
            refreshToken: null,
            user: $this->userToArray($user),
            activeRole: null,
            requiresRoleSelection: false,
            availableRoles: [],
            requiresTwoFactor: false,
            requiresTwoFactorSetup: true,
            mustChangePassword: false,
        );
    }
}
