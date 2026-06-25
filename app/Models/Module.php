<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'status',
    ];

    /**
     * Get the schools associated with this module.
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_modules')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Get the school module records for this module.
     */
    public function schoolModules(): HasMany
    {
        return $this->hasMany(SchoolModule::class);
    }
}
