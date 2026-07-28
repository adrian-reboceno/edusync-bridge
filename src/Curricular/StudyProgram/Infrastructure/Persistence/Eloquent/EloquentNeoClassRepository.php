<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Infrastructure\Persistence\Eloquent;

use Curricular\StudyProgram\Application\DTOs\NeoClassDTO;
use Curricular\StudyProgram\Domain\Ports\NeoClassRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoClassRepository implements NeoClassRepositoryContract
{
    public function upsertClass(NeoClassDTO $dto): void
    {
        $data = [
            'neo_id' => $dto->neoId,
            'parent_id' => $dto->parentId,
            'sis_id' => $dto->sisId,
            'sis_pid' => $dto->sisPid,
            'name' => $dto->name,
            'style' => $dto->style,
            'course_code' => $dto->courseCode,
            'section_code' => $dto->sectionCode,
            'organization_id' => $dto->organizationId,
            'organization_name' => $dto->organizationName,
            'start_at' => $dto->startAt,
            'finish_at' => $dto->finishAt,
            'time_zone' => $dto->timeZone,
            'archived' => $dto->archived,
            'archived_at' => $dto->archivedAt,
            'archiver_id' => $dto->archiverId,
            'private' => $dto->private,
            'access_code' => $dto->accessCode,
            'enrollment_open' => $dto->enrollmentOpen,
            'allow_reenrollment' => $dto->allowReenrollment,
            'allow_unenrollment' => $dto->allowUnenrollment,
            'used_seats' => $dto->usedSeats,
            'max_seats' => $dto->maxSeats,
            'max_students' => $dto->maxStudents,
            'tags' => json_encode($dto->tags, JSON_THROW_ON_ERROR),
            'metadata' => $dto->metadata !== null ? json_encode($dto->metadata, JSON_THROW_ON_ERROR) : null,
            'catalog_categories' => json_encode($dto->catalogCategories, JSON_THROW_ON_ERROR),
            'checksum' => $dto->checksum(),
            'synced_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_classes')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_id'],
            array_keys($data),
        );
    }

    public function hasChanged(int $neoId, string $checksum): bool
    {
        $storedChecksum = DB::table('neo_classes')
            ->where('neo_id', $neoId)
            ->value('checksum');

        return $storedChecksum !== $checksum;
    }

    public function getAllActiveClassIds(): array
    {
        return DB::table('neo_classes')
            ->where('archived', false)
            ->pluck('neo_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    public function findById(int $neoId): ?object
    {
        return DB::table('neo_classes')->where('neo_id', $neoId)->first();
    }
}
