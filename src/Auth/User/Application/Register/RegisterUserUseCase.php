<?php

declare(strict_types=1);

namespace Auth\User\Application\Register;

use Auth\User\Domain\Entities\User;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Auth\User\Domain\ValueObjects\Email;
use Auth\User\Domain\ValueObjects\Password;
use Shared\Domain\Contracts\EventBusContract;
use Shared\Domain\Exceptions\DomainException;
use Shared\Domain\ValueObjects\Uuid;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryContract $users,
        private EventBusContract $events,
    ) {}

    public function execute(RegisterUserCommand $command): User
    {
        $email = new Email($command->email);

        if ($this->users->existsByEmail($email)) {
            throw new DomainException(
                message: "A user with email {$email->toString()} already exists.",
                errorCode: 'EMAIL_ALREADY_EXISTS',
            );
        }

        $user = User::create(
            email: $email,
            password: new Password($command->password),
            firstName: $command->firstName,
            lastName: $command->lastName,
            phone: $command->phone,
            createdBy: $command->createdBy !== null ? Uuid::fromString($command->createdBy) : null,
        );

        $this->users->save($user);

        foreach ($user->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }

        return $user;
    }
}
