<?php

declare(strict_types=1);

namespace Auth\User\Application\Register;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
        public ?string $phone = null,
        public ?string $createdBy = null,
    ) {}
}
