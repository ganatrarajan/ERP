<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentParent extends Model
{
    protected $table = 'student_parents';

    protected $fillable = [
        'student_id',
        'father_name',
        'father_mobile',
        'father_email',
        'mother_name',
        'mother_mobile',
        'mother_email',
        'guardian_name',
        'guardian_mobile',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
