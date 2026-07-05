<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeAssignment extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'student_id',
        'fee_structure_id',
        'assigned_date',
        'remarks',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    protected static function booted()
    {
        $clearCache = function ($model) {
            \Illuminate\Support\Facades\Cache::forget("student_fee_dues_{$model->student_id}_{$model->academic_year_id}");
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}
