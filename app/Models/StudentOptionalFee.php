<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentOptionalFee extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'student_id',
        'fee_type_id',
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

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class, 'fee_type_id');
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
