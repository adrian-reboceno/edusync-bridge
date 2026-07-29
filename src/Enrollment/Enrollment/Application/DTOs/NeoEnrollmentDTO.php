<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Application\DTOs;

final readonly class NeoEnrollmentDTO
{
    public function __construct(
        public int $neoEnrollmentId,
        public int $neoUserId,
        public int $neoClassId,
        public ?string $sisId,
        public ?string $enrollType,
        public ?string $enrolledAt,
        public ?int $enrolledById,
        public bool $started,
        public ?string $startedAt,
        public bool $completed,
        public ?string $completedAt,
        public ?int $completedById,
        public bool $unenrolled,
        public ?string $unenrolledAt,
        public ?int $unenrolledById,
        public bool $deactivated,
        public ?string $deactivatedAt,
        public ?string $reactivatedAt,
        public bool $transferred,
        public ?string $transferredAt,
        public ?int $transferredFromId,
        public ?int $transferredToId,
        public ?string $lastVisitedAt,
        public int $timeSpentSeconds,
        public ?string $grade,
        public ?float $percent,
        public ?float $overridePercent,
        public ?string $overrideComment,
        public ?int $overrideById,
        public ?string $overrideAt,
        public bool $classArchived,
        public bool $userArchived,
    ) {}

    public static function fromApiResponse(array $data, ?string $sisId = null): self
    {
        return new self(
            neoEnrollmentId: (int) $data['id'],
            neoUserId: (int) $data['user_id'],
            neoClassId: (int) $data['class_id'],
            sisId: $sisId,
            enrollType: $data['enroll_type'] ?? null,
            enrolledAt: $data['enrolled_at'] ?? null,
            enrolledById: isset($data['enrolled_by_id']) ? (int) $data['enrolled_by_id'] : null,
            started: (bool) ($data['started'] ?? false),
            startedAt: $data['started_at'] ?? null,
            completed: (bool) ($data['completed'] ?? false),
            completedAt: $data['completed_at'] ?? null,
            completedById: isset($data['completed_by_id']) ? (int) $data['completed_by_id'] : null,
            unenrolled: (bool) ($data['unenrolled'] ?? false),
            unenrolledAt: $data['unenrolled_at'] ?? null,
            unenrolledById: isset($data['unenrolled_by_id']) ? (int) $data['unenrolled_by_id'] : null,
            deactivated: (bool) ($data['deactivated'] ?? false),
            deactivatedAt: $data['deactivated_at'] ?? null,
            reactivatedAt: $data['reactivated_at'] ?? null,
            transferred: (bool) ($data['transferred'] ?? false),
            transferredAt: $data['transferred_at'] ?? null,
            transferredFromId: isset($data['transferred_from_id']) ? (int) $data['transferred_from_id'] : null,
            transferredToId: isset($data['transferred_to_id']) ? (int) $data['transferred_to_id'] : null,
            lastVisitedAt: $data['last_visited_at'] ?? null,
            timeSpentSeconds: (int) ($data['time_spent'] ?? 0),
            grade: ($data['grade'] ?? null) === '-' ? null : ($data['grade'] ?? null),
            percent: isset($data['percent']) && $data['percent'] !== null ? (float) $data['percent'] : null,
            overridePercent: isset($data['override_percent']) && $data['override_percent'] !== null ? (float) $data['override_percent'] : null,
            overrideComment: $data['override_comment'] ?? null,
            overrideById: isset($data['override_by_id']) ? (int) $data['override_by_id'] : null,
            overrideAt: $data['override_at'] ?? null,
            classArchived: (bool) ($data['class_archived'] ?? false),
            userArchived: (bool) ($data['user_archived'] ?? false),
        );
    }

    /**
     * MD5 del subconjunto de campos relevantes para detectar cambios sin comparar todo el payload.
     */
    public function checksum(): string
    {
        return md5(implode('|', [
            $this->neoEnrollmentId,
            $this->started ? '1' : '0',
            $this->completed ? '1' : '0',
            $this->unenrolled ? '1' : '0',
            $this->deactivated ? '1' : '0',
            $this->transferred ? '1' : '0',
            $this->percent ?? '',
            $this->grade ?? '',
            $this->lastVisitedAt ?? '',
            $this->timeSpentSeconds,
        ]));
    }
}
