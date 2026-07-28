<?php

declare(strict_types=1);

namespace Grades\Grade\Application\DTOs;

final readonly class NeoAssignmentDTO
{
    public function __construct(
        public int $neoAssignmentId,
        public int $neoClassId,
        public ?int $neoLessonId,
        public ?string $lessonName,
        public ?int $creatorId,
        public ?string $type,
        public ?string $name,
        public ?float $points,
        public ?string $grading,
        public ?string $useResults,
        public ?string $category,
        public ?string $beginAt,
        public ?string $endAt,
        public bool $given,
        public ?string $givenAt,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            neoAssignmentId: (int) $data['id'],
            neoClassId: (int) $data['class_id'],
            neoLessonId: isset($data['lesson_id']) ? (int) $data['lesson_id'] : null,
            lessonName: $data['lesson_name'] ?? null,
            creatorId: isset($data['creator_id']) ? (int) $data['creator_id'] : null,
            type: $data['type'] ?? null,
            name: $data['name'] ?? null,
            points: isset($data['points']) ? (float) $data['points'] : null,
            grading: $data['grading'] ?? null,
            useResults: $data['use_results'] ?? null,
            category: $data['category'] ?? null,
            beginAt: $data['begin_at'] ?? null,
            endAt: $data['end_at'] ?? null,
            given: (bool) ($data['given'] ?? false),
            givenAt: $data['given_at'] ?? null,
        );
    }

    public function checksum(): string
    {
        return md5(implode('|', [
            $this->neoAssignmentId,
            $this->name ?? '',
            $this->points ?? '',
            $this->endAt ?? '',
            $this->given ? '1' : '0',
        ]));
    }
}
