<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'class_id',
        'section_id',
        'exam_id_1',
        'exam_id_2',
        'status',
        'template',
        'include_graded',
    ];

    /**
     * Get the school that owns this setup.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the academic year that owns this setup.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the class that this setup belongs to.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the section that this setup belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Get the first exam linked to this setup.
     */
    public function exam1(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id_1');
    }

    /**
     * Get the second exam linked to this setup.
     */
    public function exam2(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id_2');
    }
}
