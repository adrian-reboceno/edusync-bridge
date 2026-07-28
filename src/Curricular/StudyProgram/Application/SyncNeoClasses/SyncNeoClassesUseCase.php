<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\SyncNeoClasses;

use Curricular\StudyProgram\Application\DTOs\NeoClassDTO;
use Curricular\StudyProgram\Domain\Ports\NeoClassRepositoryContract;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use Throwable;

final readonly class SyncNeoClassesUseCase
{
    public function __construct(
        private NeoLmsApiContract $neoApi,
        private NeoClassRepositoryContract $repository,
    ) {}

    public function execute(SyncNeoClassesCommand $command): SyncNeoClassesResult
    {
        $classes = $this->neoApi->listClasses(
            filters: array_filter([
                'archived' => $command->includeArchived ? null : false,
                'organization_id' => $command->organizationId,
            ], static fn ($value) => $value !== null),
        );

        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($classes as $classData) {
            try {
                $dto = NeoClassDTO::fromApiResponse($classData);

                if (! $this->repository->hasChanged($dto->neoId, $dto->checksum())) {
                    $skipped++;

                    continue;
                }

                $this->repository->upsertClass($dto);
                $synced++;
            } catch (Throwable $e) {
                $errors[] = [
                    'neo_id' => $classData['id'] ?? 'unknown',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return new SyncNeoClassesResult(
            total: count($classes),
            synced: $synced,
            skipped: $skipped,
            errors: $errors,
        );
    }
}
