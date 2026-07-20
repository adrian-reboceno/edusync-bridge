<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\User\Domain;

use Auth\User\Domain\Entities\User;
use Auth\User\Domain\Events\AccountLocked;
use Auth\User\Domain\Events\LoginFailed;
use Auth\User\Domain\Exceptions\AccountLockedException;
use Auth\User\Domain\ValueObjects\Email;
use Auth\User\Domain\ValueObjects\Password;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function makeUser(string $password = 'Str0ng!Pass'): User
    {
        return User::create(
            email: new Email('jane.doe@example.com'),
            password: new Password($password),
            firstName: 'Jane',
            lastName: 'Doe',
        );
    }

    public function test_verify_password_returns_true_for_matching_password(): void
    {
        $user = $this->makeUser('Str0ng!Pass');

        self::assertTrue($user->verifyPassword(new Password('Str0ng!Pass')));
    }

    public function test_verify_password_returns_false_for_non_matching_password(): void
    {
        $user = $this->makeUser('Str0ng!Pass');

        self::assertFalse($user->verifyPassword(new Password('Different1!')));
    }

    public function test_increment_failed_attempts_increases_counter_and_records_event(): void
    {
        $user = $this->makeUser();
        $user->releaseEvents();

        $user->incrementFailedAttempts();

        self::assertSame(1, $user->getFailedLoginAttempts());

        $events = $user->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(LoginFailed::class, $events[0]);
        self::assertSame(1, $events[0]->failedAttempts);
    }

    public function test_check_lock_threshold_locks_account_after_max_attempts(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $user->incrementFailedAttempts();
        }
        $user->releaseEvents();

        $user->checkLockThreshold(maxAttempts: 5, durationMinutes: 15);

        self::assertTrue($user->getStatus()->isLocked());
        self::assertNotNull($user->getLockedUntil());

        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccountLocked::class, $events[0]);
    }

    public function test_check_lock_threshold_does_not_lock_before_max_attempts(): void
    {
        $user = $this->makeUser();

        $user->incrementFailedAttempts();
        $user->incrementFailedAttempts();
        $user->releaseEvents();

        $user->checkLockThreshold(maxAttempts: 5, durationMinutes: 15);

        self::assertFalse($user->getStatus()->isLocked());
    }

    public function test_lock_sets_status_and_locked_until_in_the_future(): void
    {
        $user = $this->makeUser();

        $user->lock(15);

        self::assertTrue($user->getStatus()->isLocked());
        self::assertGreaterThan(new \DateTimeImmutable(), $user->getLockedUntil());
    }

    public function test_assert_not_locked_throws_while_lock_window_is_active(): void
    {
        $user = $this->makeUser();
        $user->lock(15);

        $this->expectException(AccountLockedException::class);

        $user->assertNotLocked();
    }

    public function test_assert_not_locked_auto_unlocks_once_lock_window_has_passed(): void
    {
        $user = $this->makeUser();
        $user->lock(-1);

        $user->assertNotLocked();

        self::assertTrue($user->getStatus()->isActive());
        self::assertSame(0, $user->getFailedLoginAttempts());
    }

    public function test_unlock_resets_status_and_failed_attempts(): void
    {
        $user = $this->makeUser();
        $user->incrementFailedAttempts();
        $user->lock(15);

        $user->unlock();

        self::assertTrue($user->getStatus()->isActive());
        self::assertSame(0, $user->getFailedLoginAttempts());
        self::assertNull($user->getLockedUntil());
    }
}
