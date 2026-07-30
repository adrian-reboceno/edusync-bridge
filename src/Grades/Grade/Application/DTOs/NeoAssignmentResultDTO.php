<?php

declare(strict_types=1);

namespace Grades\Grade\Application\DTOs;

final readonly class NeoAssignmentResultDTO
{
    public function __construct(
        public int $neoResultId,
        public int $neoGradeId,
        public int $neoUserId,
        public int $neoClassId,
        public int $neoAssignmentId,
        public ?string $sisId,
        public ?int $questionId,
        public ?int $position,
        public ?string $response,
        public ?float $points,
        public ?float $score,
        public ?string $grade,
    ) {}

    public static function fromApiResponse(
        array $data,
        int $neoClassId,
        int $neoAssignmentId,
        ?string $sisId = null,
    ): self {
        return new self(
            neoResultId: (int) $data['id'],
            neoGradeId: (int) $data['grade_id'],
            neoUserId: (int) $data['user_id'],
            neoClassId: $neoClassId,
            neoAssignmentId: $neoAssignmentId,
            sisId: $sisId,
            questionId: isset($data['question_id']) ? (int) $data['question_id'] : null,
            position: isset($data['position']) ? (int) $data['position'] : null,
            response: $data['response'] ?? null,
            points: isset($data['points']) ? (float) $data['points'] : null,
            score: isset($data['score']) ? (float) $data['score'] : null,
            grade: $data['grade'] ?? null,
        );
    }

    public function checksum(): string
    {
        return md5(implode('|', [
            $this->neoResultId,
            $this->score ?? '',
            $this->grade ?? '',
            // response no entra en checksum — puede ser HTML largo y cambia poco
        ]));
    }
}
