<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeekendSetting extends Model
{
    protected $fillable = [
        'school_id',
        'day_name',
        'is_holiday',
        'target_type',
        'class_id',
        'section_id',
    ];

    protected $casts = [
        'is_holiday' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
