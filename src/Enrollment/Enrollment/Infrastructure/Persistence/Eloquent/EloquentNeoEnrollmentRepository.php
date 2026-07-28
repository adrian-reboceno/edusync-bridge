<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Infrastructure\Persistence\Eloquent;

use Enrollment\Enrollment\Application\DTOs\NeoEnrollmentDTO;
use Enrollment\Enrollment\Domain\Ports\NeoEnrollmentRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoEnrollmentRepository implements NeoEnrollmentRepositoryContract
{
    public function upsertEnrollment(NeoEnrollmentDTO $dto): void
    {
        $data = [
            'neo_enrollment_id' => $dto->neoEnrollmentId,
            'neo_user_id' => $dto->neoUserId,
            'neo_class_id' => $dto->neoClassId,
            'sis_id' => $dto->sisId,
            'enroll_type' => $dto->enrollType,
            'enrolled_at' => $dto->enrolledAt,
            'enrolled_by_id' => $dto->enrolledById,
            'started' => $dto->started,
            'started_at' => $dto->startedAt,
            'completed' => $dto->completed,
            'completed_at' => $dto->completedAt,
            'completed_by_id' => $dto->completedById,
            'unenrolled' => $dto->unenrolled,
            'unenrolled_at' => $dto->unenrolledAt,
            'unenrolled_by_id' => $dto->unenrolledById,
            'deactivated' => $dto->deactivated,
            'deactivated_at' => $dto->deactivatedAt,
            'reactivated_at' => $dto->reactivatedAt,
            'transferred' => $dto->transferred,
            'transferred_at' => $dto->transferredAt,
            'transferred_from_id' => $dto->transferredFromId,
            'transferred_to_id' => $dto->transferredToId,
            'last_visited_at' => $dto->lastVisitedAt,
            'time_spent_seconds' => $dto->timeSpentSeconds,
            'grade' => $dto->grade,
            'percent' => $dto->percent,
            'override_percent' => $dto->overridePercent,
            'override_comment' => $dto->overrideComment,
            'override_by_id' => $dto->overrideById,
            'override_at' => $dto->overrideAt,
            'class_archived' => $dto->classArchived,
            'user_archived' => $dto->userArchived,
            'checksum' => $dto->checksum(),
            'synced_at' => now(),
            'snapshot_date' => now()->toDateString(),
            'updated_at' => now(),
        ];

        DB::table('neo_enrollments')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_enrollment_id'],
            array_keys($data),
        );
    }

    public function hasChanged(int $enrollmentId, string $checksum): bool
    {
        $storedChecksum = DB::table('neo_enrollments')
            ->where('neo_enrollment_id', $enrollmentId)
            ->value('checksum');

        return $storedChecksum !== $checksum;
    }

    public function getSisIdMap(): array
    {
        return DB::table('neo_users')
            ->pluck('sis_id', 'neo_id')
            ->all();
    }
}
