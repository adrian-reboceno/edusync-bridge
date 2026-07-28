<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Domain\Ports;

use Curricular\CurriculumMap\Application\DTOs\NeoLessonDTO;
use Curricular\CurriculumMap\Application\DTOs\NeoSectionDTO;

interface NeoLessonRepositoryContract
{
    public function upsertLesson(NeoLessonDTO $dto): void;

    public function upsertSection(NeoSectionDTO $dto): void;

    public function hasChanged(int $neoId, string $checksum): bool;
}
