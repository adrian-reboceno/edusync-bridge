<?php

declare(strict_types=1);

namespace Grades\Grade\Application\SyncUserAssignmentGrades;

use Grades\Grade\Application\DTOs\NeoUserAssignmentGradeDTO;
use Grades\Grade\Domain\Ports\NeoUserAssignmentGradeRepositoryContract;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use Throwable;

final readonly class SyncUserAssignmentGradesUseCase
{
    public function __construct(
        private NeoLmsApiContract $neoApi,
        private NeoUserAssignmentGradeRepositoryContract $repository,
    ) {}

    public function execute(SyncUserAssignmentGradesCommand $command): SyncUserAssignmentGradesResult
    {
        $userIds = $command->userId !== null
            ? [$command->userId]
            : $this->repository->getActiveStudentIds();

        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($userIds as $userId) {
            try {
                $count = $this->neoApi->countUserAssignmentGrades($userId);

                if ($count === 0) {
                    $skipped++;

                    continue;
                }

                $sisId = $this->repository->getSisIdForUser($userId);
                $grades = $this->neoApi->getUserAssignmentGrades($userId);

                foreach ($grades as $gradeData) {
                    $dto = NeoUserAssignmentGradeDTO::fromApiResponse($gradeData, $sisId);

                    $this->repository->updateGradeTimestamps($dto);
                    $this->repository->upsertMetrics($dto);

                    $synced++;
                }
            } catch (Throwable $e) {
                $errors[] = [
                    'user_id' => $userId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return new SyncUserAssignmentGradesResult(
            synced: $synced,
            skipped: $skipped,
            errors: $errors,
        );
    }
}
