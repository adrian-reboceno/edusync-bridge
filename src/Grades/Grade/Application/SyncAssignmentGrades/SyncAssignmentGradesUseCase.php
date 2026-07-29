<?php

declare(strict_types=1);

namespace Grades\Grade\Application\SyncAssignmentGrades;

use Grades\Grade\Application\DTOs\NeoAssignmentGradeDTO;
use Grades\Grade\Domain\Ports\NeoAssignmentGradeRepositoryContract;
use Grades\Grade\Domain\Ports\NeoAssignmentRepositoryContract;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use Throwable;

final readonly class SyncAssignmentGradesUseCase
{
    public function __construct(
        private NeoLmsApiContract $neoApi,
        private NeoAssignmentGradeRepositoryContract $gradeRepository,
        private NeoAssignmentRepositoryContract $assignmentRepository,
    ) {}

    public function execute(SyncAssignmentGradesCommand $command): SyncAssignmentGradesResult
    {
        $sisIdMap = $this->gradeRepository->getUserSisIdMap();

        $assignments = $command->classId !== null
            ? $this->assignmentRepository->getAssignmentsByClass($command->classId)
            : $this->assignmentRepository->getAllAssignments();

        if ($command->assignmentId !== null) {
            $assignments = array_values(array_filter(
                $assignments,
                static fn (array $a): bool => $a['neo_assignment_id'] === $command->assignmentId,
            ));
        }

        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($assignments as $assignment) {
            try {
                $grades = $this->neoApi->getAssignmentGrades(
                    classId: $assignment['neo_class_id'],
                    assignmentId: $assignment['neo_assignment_id'],
                );

                foreach ($grades as $gradeData) {
                    $sisId = $sisIdMap[$gradeData['user_id']] ?? null;
                    $dto = NeoAssignmentGradeDTO::fromApiResponse($gradeData, $sisId);

                    if (! $this->gradeRepository->hasChanged($dto->neoGradeId, $dto->checksum())) {
                        $skipped++;

                        continue;
                    }

                    $this->gradeRepository->upsert($dto);
                    $synced++;
                }
            } catch (Throwable $e) {
                $errors[] = [
                    'class_id' => $assignment['neo_class_id'],
                    'assignment_id' => $assignment['neo_assignment_id'],
                    'message' => $e->getMessage(),
                ];
            }
        }

        return new SyncAssignmentGradesResult(
            synced: $synced,
            skipped: $skipped,
            errors: $errors,
        );
    }
}
