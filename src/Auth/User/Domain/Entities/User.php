<?php

declare(strict_types=1);

namespace Auth\User\Domain\Entities;

use Auth\User\Domain\Events\AccountLocked;
use Auth\User\Domain\Events\LoginFailed;
use Auth\User\Domain\Events\UserRegistered;
use Auth\User\Domain\Exceptions\AccountLockedException;
use Auth\User\Domain\ValueObjects\Email;
use Auth\User\Domain\ValueObjects\HashedPassword;
use Auth\User\Domain\ValueObjects\Password;
use Auth\User\Domain\ValueObjects\UserStatus;
use DateTimeImmutable;
use Shared\Domain\Contracts\DomainEvent;
use Shared\Domain\ValueObjects\Uuid;

final class User
{
    /** @var DomainEvent[] */
    private array $events = [];

    private function __construct(
        private readonly Uuid $id,
        private Email $email,
        private HashedPassword $passwordHash,
        private string $firstName,
        private string $lastName,
        private ?string $phone,
        private UserStatus $status,
        private bool $twoFactorEnabled,
        private ?string $twoFactorSecret,
        private bool $mustChangePassword,
        private ?DateTimeImmutable $passwordChangedAt,
        private int $failedLoginAttempts,
        private ?DateTimeImmutable $lockedUntil,
        private ?DateTimeImmutable $lastLoginAt,
        private ?DateTimeImmutable $lastActivityAt,
        private ?DateTimeImmutable $emailVerifiedAt,
        private readonly ?Uuid $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        Email $email,
        Password $password,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?Uuid $createdBy = null,
    ): self {
        $now = new DateTimeImmutable();

        $user = new self(
            id: Uuid::generate(),
            email: $email,
            passwordHash: $password->hash(),
            firstName: $firstName,
            lastName: $lastName,
            phone: $phone,
            status: UserStatus::PENDING_VERIFICATION,
            twoFactorEnabled: false,
            twoFactorSecret: null,
            mustChangePassword: true,
            passwordChangedAt: $now,
            failedLoginAttempts: 0,
            lockedUntil: null,
            lastLoginAt: null,
            lastActivityAt: null,
            emailVerifiedAt: null,
            createdBy: $createdBy,
            createdAt: $now,
            updatedAt: $now,
        );

        $user->record(new UserRegistered($user->id, $email->toString()));

        return $user;
    }

    public static function reconstitute(
        Uuid $id,
        Email $email,
        HashedPassword $passwordHash,
        string $firstName,
        string $lastName,
        ?string $phone,
        UserStatus $status,
        bool $twoFactorEnabled,
        ?string $twoFactorSecret,
        bool $mustChangePassword,
        ?DateTimeImmutable $passwordChangedAt,
        int $failedLoginAttempts,
        ?DateTimeImmutable $lockedUntil,
        ?DateTimeImmutable $lastLoginAt,
        ?DateTimeImmutable $lastActivityAt,
        ?DateTimeImmutable $emailVerifiedAt,
        ?Uuid $createdBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            email: $email,
            passwordHash: $passwordHash,
            firstName: $firstName,
            lastName: $lastName,
            phone: $phone,
            status: $status,
            twoFactorEnabled: $twoFactorEnabled,
            twoFactorSecret: $twoFactorSecret,
            mustChangePassword: $mustChangePassword,
            passwordChangedAt: $passwordChangedAt,
            failedLoginAttempts: $failedLoginAttempts,
            lockedUntil: $lockedUntil,
            lastLoginAt: $lastLoginAt,
            lastActivityAt: $lastActivityAt,
            emailVerifiedAt: $emailVerifiedAt,
            createdBy: $createdBy,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): HashedPassword
    {
        return $this->passwordHash;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function getLockedUntil(): ?DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getCreatedBy(): ?Uuid
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function verifyPassword(Password $plain): bool
    {
        return $this->passwordHash->verify($plain);
    }

    public function incrementFailedAttempts(): void
    {
        $this->failedLoginAttempts++;
        $this->touch();

        $this->record(new LoginFailed($this->id, $this->failedLoginAttempts, ''));
    }

    public function resetFailedAttempts(): void
    {
        $this->failedLoginAttempts = 0;
        $this->touch();
    }

    public function checkLockThreshold(int $maxAttempts = 5, int $durationMinutes = 15): void
    {
        if ($this->failedLoginAttempts < $maxAttempts) {
            return;
        }

        $this->lock($durationMinutes);
    }

    public function lock(int $durationMinutes): void
    {
        $this->status = UserStatus::LOCKED;
        $this->lockedUntil = (new DateTimeImmutable())->modify("+{$durationMinutes} minutes");
        $this->touch();

        $this->record(new AccountLocked($this->id, $this->lockedUntil, $durationMinutes));
    }

    public function unlock(): void
    {
        $this->status = UserStatus::ACTIVE;
        $this->lockedUntil = null;
        $this->failedLoginAttempts = 0;
        $this->touch();
    }

    public function assertNotLocked(): void
    {
        if (! $this->status->isLocked()) {
            return;
        }

        if ($this->lockedUntil !== null && $this->lockedUntil <= new DateTimeImmutable()) {
            $this->unlock();

            return;
        }

        throw new AccountLockedException($this->lockedUntil ?? new DateTimeImmutable());
    }

    public function checkPasswordExpiry(int $maxAgeDays = 90): bool
    {
        if ($this->passwordChangedAt === null) {
            return true;
        }

        $expiresAt = $this->passwordChangedAt->modify("+{$maxAgeDays} days");

        return $expiresAt <= new DateTimeImmutable();
    }

    /**
     * El rol requiere 2FA pero el usuario aún no lo ha configurado.
     * → Redirigir al flujo de setup TOTP (generar QR).
     */
    public function requiresTwoFactorSetup(bool $roleRequiresTwoFactor): bool
    {
        return $roleRequiresTwoFactor && ! $this->twoFactorEnabled;
    }

    /**
     * El usuario ya configuró 2FA y debe verificar el código TOTP.
     */
    public function requiresTwoFactorVerification(): bool
    {
        return $this->twoFactorEnabled && $this->twoFactorSecret !== null;
    }

    public function enableTwoFactor(string $secret): void
    {
        $this->twoFactorEnabled = true;
        $this->twoFactorSecret = $secret;
        $this->touch();
    }

    public function changePassword(HashedPassword $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
        $this->passwordChangedAt = new DateTimeImmutable();
        $this->mustChangePassword = false;
        $this->touch();
    }

    public function recordLogin(): void
    {
        $this->lastLoginAt = new DateTimeImmutable();
        $this->lastActivityAt = $this->lastLoginAt;
        $this->resetFailedAttempts();
    }

    public function verifyEmail(): void
    {
        $this->emailVerifiedAt = new DateTimeImmutable();

        if ($this->status === UserStatus::PENDING_VERIFICATION) {
            $this->status = UserStatus::ACTIVE;
        }

        $this->touch();
    }

    /**
     * @return DomainEvent[]
     */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
