<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeFineRule extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'fine_type',
        'fine_value',
        'grace_days',
        'status',
        'is_delete',
    ];

    protected $casts = [
        'academic_year_id' => 'integer',
        'fine_value' => 'decimal:2',
        'grace_days' => 'integer',
        'is_delete' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
