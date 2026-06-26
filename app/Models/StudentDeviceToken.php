<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDeviceToken extends Model
{
    protected $table = 'student_device_tokens';

    protected $fillable = [
        'school_id',
        'student_id',
        'device_type',
        'device_name',
        'app_version',
        'firebase_token',
        'last_login_at',
        'status',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'status' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
