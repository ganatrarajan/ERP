<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScale extends Model
{
    protected $fillable = [
        'school_id',
        'grade',
        'description',
        'grade_point',
        'min_percentage',
        'status',
    ];

    protected $casts = [
        'grade_point' => 'decimal:2',
        'min_percentage' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
