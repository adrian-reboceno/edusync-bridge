<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Application\SyncClassTeachers;

use Curricular\StudyProgram\Domain\Ports\NeoClassRepositoryContract;
use Enrollment\Enrollment\Application\DTOs\NeoClassTeacherDTO;
use Enrollment\Enrollment\Domain\Ports\NeoClassTeacherRepositoryContract;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use Throwable;

final readonly class SyncClassTeachersUseCase
{
    public function __construct(
        private NeoLmsApiContract $neoApi,
        private NeoClassTeacherRepositoryContract $teacherRepository,
        private NeoClassRepositoryContract $classRepository,
    ) {}

    public function execute(SyncClassTeachersCommand $command): SyncClassTeachersResult
    {
        $classes = $command->classIds ?? $this->classRepository->getAllActiveClassIds();

        $synced = [];
        $errors = [];

        foreach ($classes as $classId) {
            try {
                $teachers = $this->neoApi->getClassTeachers($classId);

                foreach ($teachers as $teacherData) {
                    $dto = NeoClassTeacherDTO::fromApiResponse($teacherData);

                    $this->teacherRepository->upsertTeacher($dto);
                    $synced[] = $dto->neoTeacherRecordId;
                }
            } catch (Throwable $e) {
                $errors[] = [
                    'class_id' => $classId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return new SyncClassTeachersResult(
            synced: count($synced),
            errors: $errors,
        );
    }
}
