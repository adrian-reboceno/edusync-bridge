<?php

declare(strict_types=1);

namespace Auth\AuditLog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class EloquentAuditLogModel extends Model
{
    protected $table = 'audit_logs';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'user_email',
        'user_role',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'status',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'timestamp' => 'datetime',
        ];
    }
}
