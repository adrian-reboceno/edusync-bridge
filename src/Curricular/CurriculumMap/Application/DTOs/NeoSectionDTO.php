<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Application\DTOs;

final readonly class NeoSectionDTO
{
    public function __construct(
        public int $neoId,
        public int $neoLessonId,
        public int $neoClassId,
        public string $name,
        public ?string $type,
        public ?string $instructions,
        public int $position,
        public int $level,
        public bool $personalized,
        public bool $optionalForCompletion,
        public ?int $referencedClassId,
    ) {}

    public static function fromApiResponse(array $data, int $neoClassId): self
    {
        return new self(
            neoId: (int) $data['id'],
            neoLessonId: (int) $data['lesson_id'],
            neoClassId: $neoClassId,
            name: $data['name'],
            type: $data['type'] ?? null,
            instructions: $data['instructions'] ?? null,
            position: (int) ($data['position'] ?? 0),
            level: (int) ($data['level'] ?? 0),
            personalized: (bool) ($data['personalized'] ?? false),
            optionalForCompletion: (bool) ($data['optional_for_completion'] ?? false),
            referencedClassId: isset($data['referenced_class_id']) ? (int) $data['referenced_class_id'] : null,
        );
    }

    public function checksum(): string
    {
        return md5(implode('|', [
            $this->neoId,
            $this->name,
            $this->type ?? '',
            $this->position,
        ]));
    }
}
