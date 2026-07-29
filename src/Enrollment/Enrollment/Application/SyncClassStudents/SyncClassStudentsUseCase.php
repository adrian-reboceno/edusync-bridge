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

                    $previous = $this->enrollmentRepository->findCurrentState($dto->neoUserId, $dto->neoClassId);
                    $isNew = $previous === null;

                    $this->enrollmentRepository->upsertEnrollment($dto);
                    $totalSynced++;

                    if ($this->hasProgressChanged($previous, $dto)) {
                        $this->enrollmentRepository->insertProgressHistory($dto, $previous);
                    }

                    foreach ($this->detectStatusEvents($previous, $dto, $isNew) as $event) {
                        $this->enrollmentRepository->insertStatusHistory($dto, $event);
                    }
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

    private function hasProgressChanged(?object $previous, NeoEnrollmentDTO $dto): bool
    {
        if ($previous === null) {
            return $dto->percent !== null || $dto->grade !== null;
        }

        return $previous->percent !== $dto->percent
            || $previous->grade !== $dto->grade
            || $previous->time_spent_seconds !== $dto->timeSpentSeconds
            || $previous->last_visited_at !== $dto->lastVisitedAt;
    }

    /**
     * @return list<string>
     */
    private function detectStatusEvents(?object $previous, NeoEnrollmentDTO $dto, bool $isNew): array
    {
        if ($isNew) {
            return ['enrolled'];
        }

        $events = [];

        if (! $previous->started && $dto->started) {
            $events[] = 'started';
        }

        if (! $previous->completed && $dto->completed) {
            $events[] = 'completed';
        }

        if (! $previous->unenrolled && $dto->unenrolled) {
            $events[] = 'unenrolled';
        }

        if (! $previous->deactivated && $dto->deactivated) {
            $events[] = 'deactivated';
        }

        if (! $previous->transferred && $dto->transferred) {
            $events[] = 'transferred';
        }

        if ($previous->deactivated && ! $dto->deactivated) {
            $events[] = 'reactivated';
        }

        return $events;
    }
}
