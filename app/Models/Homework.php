<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Homework extends Model
{
    protected $table = 'homeworks';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'subject_id',
        'title',
        'description',
        'attachment',
        'submission_date',
        'created_by',
        'status',
        'is_delete'
    ];

    public function getAttachmentAttribute($value): ?string
    {
        return \App\Helpers\UrlHelper::formatUrl($value);
    }

    public function setAttachmentAttribute($value): void
    {
        $this->attributes['attachment'] = \App\Helpers\UrlHelper::cleanRelativePath($value);
    }

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment ? \App\Helpers\UrlHelper::formatUrl($this->attachment) : null;
    }
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
