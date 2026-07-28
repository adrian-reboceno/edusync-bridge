<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Infrastructure\Persistence\Eloquent;

use Curricular\CurriculumMap\Application\DTOs\NeoLessonDTO;
use Curricular\CurriculumMap\Application\DTOs\NeoSectionDTO;
use Curricular\CurriculumMap\Domain\Ports\NeoLessonRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoLessonRepository implements NeoLessonRepositoryContract
{
    public function upsertLesson(NeoLessonDTO $dto): void
    {
        $data = [
            'neo_id' => $dto->neoId,
            'neo_class_id' => $dto->neoClassId,
            'name' => $dto->name,
            'description' => $dto->description,
            'picture' => $dto->picture,
            'notes' => $dto->notes,
            'position' => $dto->position,
            'start_at' => $dto->startAt,
            'released_at' => $dto->releasedAt,
            'begin_at' => $dto->beginAt,
            'end_at' => $dto->endAt,
            'all_day' => $dto->allDay,
            'location' => $dto->location,
            'tile_color' => $dto->tileColor,
            'personalized' => $dto->personalized,
            'optional_for_completion' => $dto->optionalForCompletion,
            'tags' => json_encode($dto->tags, JSON_THROW_ON_ERROR),
            'neo_updated_at' => $dto->updatedAt,
            'checksum' => $dto->checksum(),
            'synced_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_lessons')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_id'],
            array_keys($data),
        );
    }

    public function upsertSection(NeoSectionDTO $dto): void
    {
        $data = [
            'neo_id' => $dto->neoId,
            'neo_lesson_id' => $dto->neoLessonId,
            'neo_class_id' => $dto->neoClassId,
            'name' => $dto->name,
            'type' => $dto->type,
            'instructions' => $dto->instructions,
            'position' => $dto->position,
            'level' => $dto->level,
            'personalized' => $dto->personalized,
            'optional_for_completion' => $dto->optionalForCompletion,
            'referenced_class_id' => $dto->referencedClassId,
            'checksum' => $dto->checksum(),
            'synced_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_sections')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_id'],
            array_keys($data),
        );
    }

    public function hasChanged(int $neoId, string $checksum): bool
    {
        $storedChecksum = DB::table('neo_lessons')
            ->where('neo_id', $neoId)
            ->value('checksum');

        return $storedChecksum !== $checksum;
    }
}
