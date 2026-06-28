<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'school_id',
        'status',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);
        
        $schoolId = $attributes['school_id'] ?? (auth()->check() ? auth()->user()->school_id : null);
        $attributes['school_id'] = $schoolId;

        $params = [
            'name' => $attributes['name'], 
            'guard_name' => $attributes['guard_name'],
            'school_id' => $schoolId,
        ];

        if (static::findByParam($params)) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    public static function findByName(string $name, ?string $guardName = null): RoleContract
    {
        $guardName ??= Guard::getDefaultName(static::class);
        $schoolId = auth()->check() ? auth()->user()->school_id : null;

        $role = static::findByParam([
            'name' => $name, 
            'guard_name' => $guardName,
            'school_id' => $schoolId,
        ]);

        if (! $role) {
            throw RoleDoesNotExist::named($name, $guardName);
        }

        return $role;
    }

    public static function findOrCreate(string $name, ?string $guardName = null): RoleContract
    {
        $guardName ??= Guard::getDefaultName(static::class);
        $schoolId = auth()->check() ? auth()->user()->school_id : null;

        $attributes = [
            'name' => $name, 
            'guard_name' => $guardName,
            'school_id' => $schoolId,
        ];

        $role = static::findByParam($attributes);

        if (! $role) {
            return static::query()->create($attributes);
        }

        return $role;
    }

    protected static function findByParam(array $params = []): ?RoleContract
    {
        $query = static::query();

        if (!array_key_exists('school_id', $params)) {
            $user = auth()->user();
            if ($user && !$user->isSuperAdmin()) {
                $query->where('school_id', $user->school_id);
            }
        }

        foreach ($params as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }
}
