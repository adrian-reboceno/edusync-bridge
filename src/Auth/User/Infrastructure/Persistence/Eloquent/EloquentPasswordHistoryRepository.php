<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Persistence\Eloquent;

use Auth\User\Domain\Ports\PasswordHistoryRepositoryContract;
use Auth\User\Domain\ValueObjects\HashedPassword;
use Illuminate\Support\Str;
use Shared\Domain\ValueObjects\Uuid;

final class EloquentPasswordHistoryRepository implements PasswordHistoryRepositoryContract
{
    public function getRecentByUser(Uuid $userId, int $limit = 5): array
    {
        return EloquentPasswordHistoryModel::query()
            ->where('user_id', $userId->toString())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('password_hash')
            ->map(fn (string $hash): HashedPassword => HashedPassword::fromHash($hash))
            ->all();
    }

    public function record(Uuid $userId, HashedPassword $hash): void
    {
        EloquentPasswordHistoryModel::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId->toString(),
            'password_hash' => $hash->toString(),
            'created_at' => now(),
        ]);
    }
}
