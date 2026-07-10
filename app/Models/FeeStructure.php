<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'class_id',
        'name',
        'description',
        'status',
        'is_delete',
    ];

    protected $casts = [
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

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeeStructureItem::class, 'fee_structure_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(FeeInstallment::class, 'fee_structure_id')->where('is_delete', false);
    }

    protected static function booted()
    {
        $clearStructureCache = function ($model) {
            $assignments = \App\Models\StudentFeeAssignment::where('fee_structure_id', $model->id)->get();
            foreach ($assignments as $asn) {
                \Illuminate\Support\Facades\Cache::forget("student_fee_dues_{$asn->student_id}_{$asn->academic_year_id}");
            }
        };

        static::saved($clearStructureCache);
        static::deleted($clearStructureCache);
    }
}
