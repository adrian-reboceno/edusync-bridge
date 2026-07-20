<?php

declare(strict_types=1);

namespace Auth\User\Application\Setup2fa;

use Auth\User\Domain\Ports\TotpServiceContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Auth\User\Domain\ValueObjects\Email;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final readonly class Setup2faUseCase
{
    public function __construct(
        private UserRepositoryContract $users,
        private TotpServiceContract $totp,
    ) {}

    public function execute(Setup2faCommand $command): Setup2faResult
    {
        $user = $this->users->findByEmail(new Email($command->email));

        if ($user === null) {
            throw new \RuntimeException('User not found.');
        }

        $secret = $this->totp->generateSecret();

        $qrCodeUrl = $this->totp->getQrCodeUrl(
            secret: $secret,
            email: $user->getEmail()->toString(),
        );

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $qrSvg = base64_encode((new Writer($renderer))->writeString($qrCodeUrl));

        return new Setup2faResult(
            secret: $secret,
            qrCodeUrl: $qrCodeUrl,
            qrSvg: $qrSvg,
        );
    }
}
