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

    public function findCurrentState(int $neoUserId, int $neoClassId): ?object
    {
        $row = DB::table('neo_enrollments')
            ->where('neo_user_id', $neoUserId)
            ->where('neo_class_id', $neoClassId)
            ->select([
                'percent', 'grade', 'time_spent_seconds', 'last_visited_at',
                'started', 'completed', 'unenrolled', 'deactivated',
                'transferred', 'enrolled_at', 'enroll_type', 'enrolled_by_id',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        // Las columnas numeric() de Postgres llegan como string via PDO; se
        // normalizan aquí para que el UseCase pueda compararlas de forma estricta.
        $row->percent = $row->percent !== null ? (float) $row->percent : null;
        $row->time_spent_seconds = $row->time_spent_seconds !== null ? (int) $row->time_spent_seconds : null;

        return $row;
    }

    public function insertProgressHistory(NeoEnrollmentDTO $current, ?object $previous): void
    {
        DB::table('neo_enrollment_progress_history')->insert([
            'neo_user_id' => $current->neoUserId,
            'neo_class_id' => $current->neoClassId,
            'sis_id' => $current->sisId,

            'percent' => $current->percent,
            'grade' => $current->grade,
            'time_spent_seconds' => $current->timeSpentSeconds,
            'last_visited_at' => $current->lastVisitedAt,

            'percent_changed' => ($previous?->percent ?? null) !== $current->percent,
            'grade_changed' => ($previous?->grade ?? null) !== ($current->grade === '-' ? null : $current->grade),
            'time_spent_changed' => (int) ($previous?->time_spent_seconds ?? 0) !== $current->timeSpentSeconds,
            'last_visited_changed' => ($previous?->last_visited_at ?? null) !== $current->lastVisitedAt,
            'prev_percent' => $previous?->percent ?? null,
            'prev_grade' => $previous?->grade ?? null,
            'prev_time_spent_seconds' => $previous !== null ? (int) $previous->time_spent_seconds : null,

            'recorded_at' => now(),
        ]);
    }

    public function insertStatusHistory(NeoEnrollmentDTO $current, string $event): void
    {
        DB::table('neo_enrollment_status_history')->insert([
            'neo_enrollment_id' => $current->neoEnrollmentId,
            'neo_user_id' => $current->neoUserId,
            'neo_class_id' => $current->neoClassId,
            'sis_id' => $current->sisId,
            'event' => $event,
            'enroll_type' => $current->enrollType,
            'enrolled_by_id' => $current->enrolledById,
            'enrolled_at' => $current->enrolledAt,
            'unenrolled' => $current->unenrolled,
            'deactivated' => $current->deactivated,
            'transferred' => $current->transferred,
            'completed' => $current->completed,
            'started' => $current->started,
            'recorded_at' => now(),
        ]);
    }
}
