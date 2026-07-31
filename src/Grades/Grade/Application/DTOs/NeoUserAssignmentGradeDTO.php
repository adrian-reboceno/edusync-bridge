<?php

declare(strict_types=1);

namespace Grades\Grade\Application\DTOs;

use DateTimeImmutable;

final readonly class NeoUserAssignmentGradeDTO
{
    public function __construct(
        public int $neoGradeId,
        public int $neoUserId,
        public int $neoAssignmentId,
        public int $neoClassId,
        public ?int $lessonId,
        public ?string $lessonName,
        public ?string $sisId,
        public ?int $graderId,
        public bool $started,
        public ?string $startedAt,
        public bool $finished,
        public ?string $finishedAt,
        public int $graded,
        public ?string $gradedAt,
        public bool $fullyGraded,
        public ?float $score,
        public ?float $percent,
        public ?string $grade,
        public ?float $points,
        public ?float $minPoints,
        public bool $missing,
        public bool $absent,
        public bool $excused,
        public bool $incomplete,
        public ?string $excusedComment,
        public ?string $teacherComment,
        public ?string $assignmentType,
        public ?string $assignmentName,
        public ?float $assignmentPoints,
        public ?string $assignmentBeginAt,
        public ?string $assignmentEndAt,
        public ?string $assignmentGivenAt,
        public ?string $assignmentGrading,
    ) {}

    public static function fromApiResponse(array $data, ?string $sisId = null): self
    {
        $assignment = $data['assignment'] ?? [];

        return new self(
            neoGradeId: (int) $data['id'],
            neoUserId: (int) $data['user_id'],
            neoAssignmentId: (int) $data['assignment_id'],
            neoClassId: (int) $data['class_id'],
            lessonId: isset($data['lesson_id']) ? (int) $data['lesson_id'] : null,
            lessonName: $data['lesson_name'] ?? null,
            sisId: $sisId,
            graderId: isset($data['grader_id']) ? (int) $data['grader_id'] : null,
            started: (bool) ($data['started'] ?? false),
            startedAt: $data['started_at'] ?? null,
            finished: (bool) ($data['finished'] ?? false),
            finishedAt: $data['finished_at'] ?? null,
            graded: (int) ($data['graded'] ?? 0),
            gradedAt: $data['graded_at'] ?? null,
            fullyGraded: (bool) ($data['fully_graded'] ?? false),
            score: isset($data['score']) ? (float) $data['score'] : null,
            percent: isset($data['percent']) ? (float) $data['percent'] : null,
            grade: $data['grade'] ?? null,
            points: isset($data['points']) ? (float) $data['points'] : null,
            minPoints: isset($data['min_points']) ? (float) $data['min_points'] : null,
            missing: (bool) ($data['missing'] ?? false),
            absent: (bool) ($data['absent'] ?? false),
            excused: (bool) ($data['excused'] ?? false),
            incomplete: (bool) ($data['incomplete'] ?? false),
            excusedComment: $data['excused_comment'] ?? null,
            teacherComment: $data['teacher_comment'] ?? null,
            assignmentType: $assignment['type'] ?? null,
            assignmentName: $assignment['name'] ?? null,
            assignmentPoints: isset($assignment['points']) ? (float) $assignment['points'] : null,
            assignmentBeginAt: $assignment['begin_at'] ?? null,
            assignmentEndAt: $assignment['end_at'] ?? null,
            assignmentGivenAt: $assignment['given_at'] ?? null,
            assignmentGrading: $assignment['grading'] ?? null,
        );
    }

    public function durationMinutes(): ?float
    {
        if ($this->startedAt === null || $this->finishedAt === null) {
            return null;
        }

        $diff = (new DateTimeImmutable($this->finishedAt))->getTimestamp()
            - (new DateTimeImmutable($this->startedAt))->getTimestamp();

        return $diff > 0 ? round($diff / 60, 2) : 0.0;
    }

    public function feedbackMinutes(): ?float
    {
        if ($this->finishedAt === null || $this->gradedAt === null) {
            return null;
        }

        $diff = (new DateTimeImmutable($this->gradedAt))->getTimestamp()
            - (new DateTimeImmutable($this->finishedAt))->getTimestamp();

        return $diff >= 0 ? round($diff / 60, 2) : null;
    }

    public function timeToStartMinutes(): ?float
    {
        if ($this->assignmentGivenAt === null || $this->startedAt === null) {
            return null;
        }

        $diff = (new DateTimeImmutable($this->startedAt))->getTimestamp()
            - (new DateTimeImmutable($this->assignmentGivenAt))->getTimestamp();

        return $diff >= 0 ? round($diff / 60, 2) : null;
    }

    public function submittedOnTime(): ?bool
    {
        if ($this->finishedAt === null || $this->assignmentEndAt === null) {
            return null;
        }

        return (new DateTimeImmutable($this->finishedAt))->getTimestamp()
            <= (new DateTimeImmutable($this->assignmentEndAt))->getTimestamp();
    }

    public function minutesBeforeDeadline(): ?float
    {
        if ($this->finishedAt === null || $this->assignmentEndAt === null) {
            return null;
        }

        $diff = (new DateTimeImmutable($this->assignmentEndAt))->getTimestamp()
            - (new DateTimeImmutable($this->finishedAt))->getTimestamp();

        return round($diff / 60, 2);
    }
}
