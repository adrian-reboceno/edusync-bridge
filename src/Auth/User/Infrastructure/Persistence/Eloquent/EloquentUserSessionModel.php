<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class EloquentUserSessionModel extends Model
{
    protected $table = 'user_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'client_type',
        'access_token_hash',
        'refresh_token_hash',
        'access_expires_at',
        'refresh_expires_at',
        'active_role_id',
        'role_activated_at',
        'last_activity_at',
        'ip_address',
        'user_agent',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'access_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'role_activated_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
