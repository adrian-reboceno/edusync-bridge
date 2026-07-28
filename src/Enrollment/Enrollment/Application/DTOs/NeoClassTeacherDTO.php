<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Application\DTOs;

final readonly class NeoClassTeacherDTO
{
    public function __construct(
        public int $neoTeacherRecordId,
        public int $neoUserId,
        public int $neoClassId,
        public bool $coteacher,
        public ?string $lastVisitedAt,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            neoTeacherRecordId: (int) $data['id'],
            neoUserId: (int) $data['user_id'],
            neoClassId: (int) $data['class_id'],
            coteacher: (bool) ($data['coteacher'] ?? false),
            lastVisitedAt: $data['last_visited_at'] ?? null,
        );
    }
}
