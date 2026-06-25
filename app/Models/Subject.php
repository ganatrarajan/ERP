<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'name',
        'code',
        'description',
        'status',
        'is_optional',
        'is_delete',
        'evaluation_type',
        'subject_category',
        'maximum_marks',
        'passing_marks',
        'grade_scale_id',
    ];

    protected $casts = [
        'grade_scale_id' => 'array',
        'is_optional' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function gradeScale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class, 'grade_scale_id');
    }
}
