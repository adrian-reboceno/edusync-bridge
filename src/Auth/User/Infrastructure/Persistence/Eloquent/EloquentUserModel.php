<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentUserModel extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'phone',
        'status',
        'two_factor_enabled',
        'two_factor_secret',
        'must_change_password',
        'password_changed_at',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'last_activity_at',
        'email_verified_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'two_factor_enabled' => 'boolean',
            'must_change_password' => 'boolean',
            'failed_login_attempts' => 'integer',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }
}
