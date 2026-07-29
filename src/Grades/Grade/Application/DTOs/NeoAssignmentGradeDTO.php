<?php

declare(strict_types=1);

namespace Grades\Grade\Application\DTOs;

final readonly class NeoAssignmentGradeDTO
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
    ) {}

    public static function fromApiResponse(array $data, ?string $sisId = null): self
    {
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
        );
    }

    public function checksum(): string
    {
        return md5(implode('|', [
            $this->neoGradeId,
            $this->score ?? '',
            $this->percent ?? '',
            $this->grade ?? '',
            $this->fullyGraded ? '1' : '0',
            $this->graded,
            $this->finished ? '1' : '0',
            $this->excused ? '1' : '0',
            $this->absent ? '1' : '0',
            $this->teacherComment ?? '',
        ]));
    }
}
