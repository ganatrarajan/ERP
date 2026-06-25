<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeType extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_optional',
        'status',
        'is_delete',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'is_delete' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
