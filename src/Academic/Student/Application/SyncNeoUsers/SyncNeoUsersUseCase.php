<?php

declare(strict_types=1);

namespace Academic\Student\Application\SyncNeoUsers;

use Academic\Student\Application\DTOs\NeoUserDTO;
use Academic\Student\Application\DTOs\NeoUserSessionDTO;
use Academic\Student\Domain\Ports\NeoUserRepositoryContract;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use Throwable;

final readonly class SyncNeoUsersUseCase
{
    public function __construct(
        private NeoLmsApiContract $neoApi,
        private NeoUserRepositoryContract $repository,
    ) {}

    public function execute(SyncNeoUsersCommand $command): SyncNeoUsersResult
    {
        $synced = 0;
        $skipped = 0;
        $errors = [];

        $users = $this->neoApi->listUsers(
            filters: array_filter([
                'archived' => $command->includeArchived ? null : false,
                'organization_id' => $command->organizationId,
            ], static fn ($value) => $value !== null),
            include: 'organization,job_title',
        );

        foreach ($users as $userData) {
            try {
                $dto = NeoUserDTO::fromApiResponse($userData);

                if (! $this->repository->hasChanged($dto->neoId, $dto->checksum())) {
                    $skipped++;

                    continue;
                }

                $this->repository->upsert($dto);
                $synced++;

                $this->syncSessionsFor($dto);
            } catch (Throwable $e) {
                $errors[] = [
                    'neo_id' => $userData['id'] ?? 'unknown',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return new SyncNeoUsersResult(
            synced: $synced,
            skipped: $skipped,
            errors: $errors,
            total: count($users),
        );
    }

    private function syncSessionsFor(NeoUserDTO $dto): void
    {
        $lastSessionId = $this->repository->getLastSessionId($dto->neoId);

        $sessions = $this->neoApi->getUserSessions(
            neoUserId: $dto->neoId,
            after: $lastSessionId,
        );

        if ($sessions === []) {
            return;
        }

        $sessionDtos = array_map(
            static fn (array $s): NeoUserSessionDTO => NeoUserSessionDTO::fromApiResponse($s, sisId: $dto->sisId),
            $sessions,
        );

        $this->repository->insertSessions($sessionDtos);
    }
}
