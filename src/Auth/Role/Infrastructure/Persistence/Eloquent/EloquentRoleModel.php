<?php

declare(strict_types=1);

namespace Auth\Role\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

final class EloquentRoleModel extends SpatieRole
{
    protected $table = 'roles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'display_name',
        'guard_name',
        'hierarchy_level',
        'is_system',
        'two_factor_required',
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

    protected function casts(): array
    {
        return [
            'hierarchy_level' => 'integer',
            'is_system' => 'boolean',
            'two_factor_required' => 'boolean',
        ];
    }
}
