<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\DTOs;

final readonly class NeoClassDTO
{
    public function __construct(
        public int $neoId,
        public ?int $parentId,
        public string $name,
        public ?string $style,
        public ?string $sisId,
        public ?string $sisPid,
        public ?string $courseCode,
        public ?string $sectionCode,
        public ?int $organizationId,
        public ?string $organizationName,
        public ?string $startAt,
        public ?string $finishAt,
        public ?string $timeZone,
        public bool $archived,
        public ?string $archivedAt,
        public ?int $archiverId,
        public bool $private,
        public ?string $accessCode,
        public bool $enrollmentOpen,
        public bool $allowReenrollment,
        public bool $allowUnenrollment,
        public int $usedSeats,
        public ?int $maxSeats,
        public ?int $maxStudents,
        public array $tags,
        public ?array $metadata,
        public array $catalogCategories,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            neoId: (int) $data['id'],
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            name: $data['name'],
            style: $data['style'] ?? null,
            sisId: $data['sis_id'] ?? null,
            sisPid: $data['sis_pid'] ?? null,
            courseCode: $data['course_code'] ?? null,
            sectionCode: $data['section_code'] ?? null,
            organizationId: isset($data['organization_id']) ? (int) $data['organization_id'] : null,
            organizationName: $data['organization_name'] ?? null,
            startAt: $data['start_at'] ?? null,
            finishAt: $data['finish_at'] ?? null,
            timeZone: $data['time_zone'] ?? null,
            archived: (bool) ($data['archived'] ?? false),
            archivedAt: $data['archived_at'] ?? null,
            archiverId: isset($data['archiver_id']) ? (int) $data['archiver_id'] : null,
            private: (bool) ($data['private'] ?? false),
            accessCode: $data['access_code'] ?? null,
            enrollmentOpen: (bool) ($data['enrollment_open'] ?? true),
            allowReenrollment: (bool) ($data['allow_reenrollment'] ?? false),
            allowUnenrollment: (bool) ($data['allow_unenrollment'] ?? true),
            usedSeats: (int) ($data['used_seats'] ?? 0),
            maxSeats: isset($data['max_seats']) ? (int) $data['max_seats'] : null,
            maxStudents: isset($data['max_students']) ? (int) $data['max_students'] : null,
            tags: $data['tags'] ?? [],
            metadata: $data['metadata'] ?? null,
            catalogCategories: $data['catalog_categories'] ?? [],
        );
    }

    /**
     * MD5 del subconjunto de campos relevantes para detectar cambios sin comparar todo el payload.
     */
    public function checksum(): string
    {
        return md5(implode('|', [
            $this->neoId,
            $this->name,
            $this->sisId ?? '',
            $this->archived ? '1' : '0',
            $this->usedSeats,
            $this->organizationId ?? '',
        ]));
    }
}
