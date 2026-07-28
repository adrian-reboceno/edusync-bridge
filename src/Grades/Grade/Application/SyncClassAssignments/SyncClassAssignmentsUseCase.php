<?php

declare(strict_types=1);

namespace Grades\Grade\Application\SyncClassAssignments;

use Curricular\StudyProgram\Domain\Ports\NeoClassRepositoryContract;
use Grades\Grade\Application\DTOs\NeoAssignmentDTO;
use Grades\Grade\Domain\Ports\NeoAssignmentRepositoryContract;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use Throwable;

final readonly class SyncClassAssignmentsUseCase
{
    public function __construct(
        private NeoLmsApiContract $neoApi,
        private NeoAssignmentRepositoryContract $repository,
        private NeoClassRepositoryContract $classRepository,
    ) {}

    public function execute(SyncClassAssignmentsCommand $command): SyncClassAssignmentsResult
    {
        $classIds = $command->classIds ?? $this->classRepository->getAllActiveClassIds();

        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($classIds as $classId) {
            try {
                $assignments = $this->neoApi->getClassAssignments($classId);

                foreach ($assignments as $assignmentData) {
                    $dto = NeoAssignmentDTO::fromApiResponse($assignmentData);

                    if (! $this->repository->hasChanged($dto->neoAssignmentId, $dto->checksum())) {
                        $skipped++;

                        continue;
                    }

                    $this->repository->upsertAssignment($dto);
                    $synced++;
                }
            } catch (Throwable $e) {
                $errors[] = ['class_id' => $classId, 'message' => $e->getMessage()];
            }
        }

        return new SyncClassAssignmentsResult(
            synced: $synced,
            skipped: $skipped,
            errors: $errors,
        );
    }
}
