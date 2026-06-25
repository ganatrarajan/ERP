<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeDiscount extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'student_id',
        'discount_type',
        'discount_value',
        'reason',
        'start_date',
        'end_date',
        'status',
        'max_uses',
        'used_count',
        'is_delete',
    ];

    protected $casts = [
        'academic_year_id' => 'integer',
        'discount_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'is_delete' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
