<?php

declare(strict_types=1);

namespace Academic\Student\Infrastructure\Persistence\Eloquent;

use Academic\Student\Application\DTOs\NeoUserDTO;
use Academic\Student\Application\DTOs\NeoUserSessionDTO;
use Academic\Student\Domain\Ports\NeoUserRepositoryContract;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentNeoUserRepository implements NeoUserRepositoryContract
{
    public function upsert(NeoUserDTO $dto): void
    {
        $data = [
            'neo_id' => $dto->neoId,
            'sis_id' => $dto->sisId,
            'sis_pid' => $dto->sisPid,
            'userid' => $dto->userid,
            'studentid' => $dto->studentId,
            'teacherid' => $dto->teacherId,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'nick_name' => $dto->nickName,
            'email' => $dto->email,
            'gender' => $dto->gender,
            'birthdate' => $dto->birthdate,
            'year_of_graduation' => $dto->yearOfGraduation,
            'phone' => $dto->phone,
            'mobile_phone' => $dto->mobilePhone,
            'country' => $dto->country,
            'city' => $dto->city,
            'state' => $dto->state,
            'zip' => $dto->zip,
            'roles' => json_encode($dto->roles, JSON_THROW_ON_ERROR),
            'organization_id' => $dto->organizationId,
            'organization_name' => $dto->organizationName,
            'job_title_id' => $dto->jobTitleId,
            'job_title_name' => $dto->jobTitleName,
            'manager_id' => $dto->managerId,
            'manager_name' => $dto->managerName,
            'added_by_id' => $dto->addedById,
            'language' => $dto->language,
            'time_zone' => $dto->timeZone,
            'email_sync' => $dto->emailSync,
            'sms_sync' => $dto->smsSync,
            'tags' => json_encode($dto->tags, JSON_THROW_ON_ERROR),
            'custom_fields' => json_encode($dto->customFields, JSON_THROW_ON_ERROR),
            'joined_at' => $dto->joinedAt,
            'first_login_at' => $dto->firstLoginAt,
            'last_login_at' => $dto->lastLoginAt,
            'last_login_ip' => $dto->lastLoginIp,
            'archived' => $dto->archived,
            'archived_at' => $dto->archivedAt,
            'archiver_id' => $dto->archiverId,
            'organization_data' => $dto->organizationData !== null
                ? json_encode($dto->organizationData, JSON_THROW_ON_ERROR)
                : null,
            'checksum' => $dto->checksum(),
            'synced_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_users')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_id'],
            array_keys($data),
        );
    }

    public function hasChanged(int $neoId, string $newChecksum): bool
    {
        $storedChecksum = DB::table('neo_users')
            ->where('neo_id', $neoId)
            ->value('checksum');

        return $storedChecksum !== $newChecksum;
    }

    public function countByRole(): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT role, COUNT(*) AS total
            FROM neo_users, jsonb_array_elements_text(roles) AS role
            WHERE archived = false
            GROUP BY role
            ORDER BY total DESC
        SQL);

        $result = [];
        foreach ($rows as $row) {
            $result[$row->role] = (int) $row->total;
        }

        return $result;
    }

    public function findNeverLoggedIn(?int $organizationId = null): array
    {
        $query = DB::table('neo_users')
            ->where('archived', false)
            ->whereNotNull('joined_at')
            ->whereNull('first_login_at');

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    public function findInactiveSince(int $days, ?int $organizationId = null): array
    {
        $query = DB::table('neo_users')
            ->where('archived', false)
            ->where('last_login_at', '<', now()->subDays($days));

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    public function insertSessions(array $sessions): int
    {
        $inserted = 0;

        foreach ($sessions as $session) {
            /** @var NeoUserSessionDTO $session */
            $inserted += DB::table('neo_user_sessions')->upsert(
                [[
                    'neo_session_id' => $session->neoSessionId,
                    'neo_user_id' => $session->neoUserId,
                    'sis_id' => $session->sisId,
                    'login_at' => $session->loginAt,
                    'logout_at' => $session->logoutAt,
                    'duration_seconds' => $session->durationSeconds(),
                    'ip_address' => $session->ipAddress,
                    'synced_at' => now(),
                    'created_at' => now(),
                ]],
                ['neo_session_id'],
                ['logout_at', 'duration_seconds', 'synced_at'],
            );
        }

        return $inserted;
    }

    public function getLastSessionId(int $neoUserId): ?int
    {
        $max = DB::table('neo_user_sessions')
            ->where('neo_user_id', $neoUserId)
            ->max('neo_session_id');

        return $max !== null ? (int) $max : null;
    }

    public function countSessionsByUser(int $neoUserId, ?DateTimeImmutable $from = null): int
    {
        $query = DB::table('neo_user_sessions')->where('neo_user_id', $neoUserId);

        if ($from !== null) {
            $query->where('login_at', '>=', $from);
        }

        return $query->count();
    }
}
