<?php

declare(strict_types=1);

namespace Auth\Role\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as SpatiePermission;

final class EloquentPermissionModel extends SpatiePermission
{
    protected $table = 'permissions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'display_name',
        'guard_name',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if ($model->id === null) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
