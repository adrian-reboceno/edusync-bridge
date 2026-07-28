<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Application\SyncClassStudents;

use Curricular\StudyProgram\Domain\Ports\NeoClassRepositoryContract;
use Enrollment\Enrollment\Application\DTOs\NeoEnrollmentDTO;
use Enrollment\Enrollment\Domain\Ports\NeoEnrollmentRepositoryContract;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use Throwable;

final readonly class SyncClassStudentsUseCase
{
    public function __construct(
        private NeoLmsApiContract $neoApi,
        private NeoEnrollmentRepositoryContract $enrollmentRepository,
        private NeoClassRepositoryContract $classRepository,
    ) {}

    public function execute(SyncClassStudentsCommand $command): SyncClassStudentsResult
    {
        $sisIdMap = $this->enrollmentRepository->getSisIdMap();

        $classes = $command->classIds ?? $this->classRepository->getAllActiveClassIds();

        $totalSynced = 0;
        $totalSkipped = 0;
        $errors = [];

        foreach ($classes as $classId) {
            try {
                $students = $this->neoApi->getClassStudents(
                    classId: $classId,
                    filters: ['unenrolled' => false],
                );

                foreach ($students as $studentData) {
                    $sisId = $sisIdMap[$studentData['user_id']] ?? null;
                    $dto = NeoEnrollmentDTO::fromApiResponse($studentData, $sisId);

                    if (! $this->enrollmentRepository->hasChanged($dto->neoEnrollmentId, $dto->checksum())) {
                        $totalSkipped++;

                        continue;
                    }

                    $this->enrollmentRepository->upsertEnrollment($dto);
                    $totalSynced++;
                }
            } catch (Throwable $e) {
                $errors[] = [
                    'class_id' => $classId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return new SyncClassStudentsResult(
            synced: $totalSynced,
            skipped: $totalSkipped,
            errors: $errors,
        );
    }
}
