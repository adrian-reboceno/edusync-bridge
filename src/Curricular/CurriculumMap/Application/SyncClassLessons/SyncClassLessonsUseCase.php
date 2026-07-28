<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Application\SyncClassLessons;

use Curricular\CurriculumMap\Application\DTOs\NeoLessonDTO;
use Curricular\CurriculumMap\Application\DTOs\NeoSectionDTO;
use Curricular\CurriculumMap\Domain\Ports\NeoLessonRepositoryContract;
use Curricular\StudyProgram\Domain\Ports\NeoClassRepositoryContract;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use Throwable;

final readonly class SyncClassLessonsUseCase
{
    public function __construct(
        private NeoLmsApiContract $neoApi,
        private NeoLessonRepositoryContract $repository,
        private NeoClassRepositoryContract $classRepository,
    ) {}

    public function execute(SyncClassLessonsCommand $command): SyncClassLessonsResult
    {
        $classIds = $command->classIds ?? $this->classRepository->getAllActiveClassIds();

        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($classIds as $classId) {
            try {
                $lessons = $this->neoApi->getClassLessons($classId);

                foreach ($lessons as $lessonData) {
                    $dto = NeoLessonDTO::fromApiResponse($lessonData);

                    if (! $this->repository->hasChanged($dto->neoId, $dto->checksum())) {
                        $skipped++;

                        continue;
                    }

                    $this->repository->upsertLesson($dto);
                    $synced++;
                }

                $sections = $this->neoApi->getClassSections($classId);

                foreach ($sections as $sectionData) {
                    $dto = NeoSectionDTO::fromApiResponse($sectionData, $classId);
                    $this->repository->upsertSection($dto);
                }
            } catch (Throwable $e) {
                $errors[] = ['class_id' => $classId, 'message' => $e->getMessage()];
            }
        }

        return new SyncClassLessonsResult(
            synced: $synced,
            skipped: $skipped,
            errors: $errors,
        );
    }
}
