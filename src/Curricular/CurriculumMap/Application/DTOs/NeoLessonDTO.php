<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Application\DTOs;

final readonly class NeoLessonDTO
{
    public function __construct(
        public int $neoId,
        public int $neoClassId,
        public string $name,
        public ?string $description,
        public ?string $picture,
        public ?string $notes,
        public int $position,
        public ?string $startAt,
        public ?string $releasedAt,
        public ?string $beginAt,
        public ?string $endAt,
        public bool $allDay,
        public ?string $location,
        public ?string $tileColor,
        public bool $personalized,
        public bool $optionalForCompletion,
        public ?string $updatedAt,
        public array $tags,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            neoId: (int) $data['id'],
            neoClassId: (int) $data['class_id'],
            name: $data['name'],
            description: $data['description'] ?? null,
            picture: $data['picture'] ?? null,
            notes: $data['notes'] ?? null,
            position: (int) ($data['position'] ?? 0),
            startAt: $data['start_at'] ?? null,
            releasedAt: $data['released_at'] ?? null,
            beginAt: $data['begin_at'] ?? null,
            endAt: $data['end_at'] ?? null,
            allDay: (bool) ($data['all_day'] ?? true),
            location: $data['location'] ?? null,
            tileColor: $data['tile_color'] ?? null,
            personalized: (bool) ($data['personalized'] ?? false),
            optionalForCompletion: (bool) ($data['optional_for_completion'] ?? false),
            updatedAt: $data['updated_at'] ?? null,
            tags: $data['tags'] ?? [],
        );
    }

    public function checksum(): string
    {
        return md5(implode('|', [
            $this->neoId,
            $this->name,
            $this->position,
            $this->updatedAt ?? '',
        ]));
    }
}
