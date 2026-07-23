<?php

declare(strict_types=1);

namespace Academic\Student\Application\DTOs;

final readonly class NeoUserDTO
{
    public function __construct(
        public int     $neoId,
        public ?string $sisId,
        public ?string $sisPid,
        public ?string $userid,
        public ?string $studentId,
        public ?string $teacherId,
        public string  $firstName,
        public string  $lastName,
        public ?string $nickName,
        public ?string $email,
        public ?string $gender,
        public ?string $birthdate,
        public ?int    $yearOfGraduation,
        public ?string $phone,
        public ?string $mobilePhone,
        public ?string $country,
        public ?string $city,
        public ?string $state,
        public ?string $zip,
        public array   $roles,
        public ?int    $organizationId,
        public ?string $organizationName,
        public ?int    $jobTitleId,
        public ?string $jobTitleName,
        public ?int    $managerId,
        public ?string $managerName,
        public ?int    $addedById,
        public ?string $language,
        public ?string $timeZone,
        public ?string $emailSync,
        public ?string $smsSync,
        public array   $tags,
        public array   $customFields,
        public ?string $joinedAt,
        public ?string $firstLoginAt,
        public ?string $lastLoginAt,
        public ?string $lastLoginIp,
        public bool    $archived,
        public ?string $archivedAt,
        public ?int    $archiverId,
        public ?array  $organizationData,
    ) {}

    /**
     * Construye el DTO desde el array de la respuesta de la API NEO LMS.
     * Maneja la diferencia de casing: studentID -> studentId.
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            neoId:            (int) $data['id'],
            sisId:            $data['sis_id'] ?? null,
            sisPid:           $data['sis_pid'] ?? null,
            userid:           $data['userid'] ?? null,
            studentId:        $data['studentID'] ?? null,
            teacherId:        $data['teacherID'] ?? null,
            firstName:        $data['first_name'],
            lastName:         $data['last_name'],
            nickName:         $data['nick_name'] ?? null,
            email:            $data['email'] ?? null,
            gender:           $data['gender'] ?? null,
            birthdate:        $data['birthdate'] ?? null,
            yearOfGraduation: isset($data['year_of_graduation']) ? (int) $data['year_of_graduation'] : null,
            phone:            $data['phone'] ?? null,
            mobilePhone:      $data['mobile_phone'] ?? null,
            country:          $data['country'] ?? null,
            city:             $data['city'] ?? null,
            state:            $data['state'] ?? null,
            zip:              $data['zip'] ?? null,
            roles:            $data['roles'] ?? [],
            organizationId:   isset($data['organization_id']) ? (int) $data['organization_id'] : null,
            organizationName: $data['organization_name'] ?? null,
            jobTitleId:       isset($data['job_title_id']) ? (int) $data['job_title_id'] : null,
            jobTitleName:     $data['job_title_name'] ?? null,
            managerId:        isset($data['manager_id']) ? (int) $data['manager_id'] : null,
            managerName:      $data['manager_name'] ?? null,
            addedById:        isset($data['added_by_id']) ? (int) $data['added_by_id'] : null,
            language:         $data['language'] ?? null,
            timeZone:         $data['time_zone'] ?? null,
            emailSync:        $data['email_sync'] ?? null,
            smsSync:          $data['sms_sync'] ?? null,
            tags:             $data['tags'] ?? [],
            customFields:     is_array($data['custom_fields'] ?? null) ? $data['custom_fields'] : [],
            joinedAt:         $data['joined_at'] ?? null,
            firstLoginAt:     $data['first_login_at'] ?? null,
            lastLoginAt:      $data['last_login_at'] ?? null,
            lastLoginIp:      $data['last_login_ip'] ?? null,
            archived:         (bool) ($data['archived'] ?? false),
            archivedAt:       $data['archived_at'] ?? null,
            archiverId:       isset($data['archiver_id']) ? (int) $data['archiver_id'] : null,
            organizationData: $data['organization'] ?? null,
        );
    }

    /**
     * MD5 del subconjunto de campos relevantes para detectar cambios sin comparar todo el payload.
     */
    public function checksum(): string
    {
        return md5(implode('|', [
            $this->neoId,
            $this->sisId ?? '',
            $this->firstName,
            $this->lastName,
            $this->email ?? '',
            implode(',', $this->roles),
            $this->lastLoginAt ?? '',
            $this->archived ? '1' : '0',
            $this->organizationId ?? '',
        ]));
    }
}
