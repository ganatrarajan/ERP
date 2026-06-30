<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'school_id',
        'admission_no',
        'gr_no',
        'first_name',
        'last_name',
        'gender',
        'category',
        'religion',
        'nationality',
        'aadhaar_no',
        'pen_no',
        'udise_no',
        'date_of_birth',
        'blood_group',
        'mobile',
        'email',
        'address',
        'previous_school_name',
        'previous_school_tc_no',
        'previous_school_tc_date',
        'emergency_contact_name',
        'emergency_contact_mobile',
        'emergency_contact_email',
        'photo',
        'admission_date',
        'status',
        'house',
        'is_delete',
        'password',
        'password_changed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'previous_school_tc_date' => 'date',
        'password_changed' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($student) {
            if (empty($student->password)) {
                $student->password = bcrypt($student->admission_no);
            }
            if (!isset($student->password_changed)) {
                $student->password_changed = 0;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function parent(): HasOne
    {
        return $this->hasOne(StudentParent::class, 'student_id');
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(StudentAcademicRecord::class, 'student_id');
    }

    public function currentAcademicRecord()
    {
        $academicYearId = null;

        if (request()->is('api/mobile/*') || request()->is('mobile/*')) {
            $schoolId = request()->attributes->get('school_id');
            if ($schoolId) {
                $school = School::find($schoolId);
                $academicYearId = $school ? $school->mobile_academic_year_id : null;
            }
            if (!$academicYearId && auth()->guard('sanctum')->check()) {
                $student = auth()->guard('sanctum')->user();
                if ($student) {
                    $school = School::find($student->school_id);
                    $academicYearId = $school ? $school->mobile_academic_year_id : null;
                }
            }
        } else {
            $user = auth()->user();
            if ($user) {
                $academicYearId = \App\Support\AcademicYearContext::getActiveAcademicYearId($user->school_id);
            }
        }

        if ($academicYearId) {
            return $this->hasOne(StudentAcademicRecord::class, 'student_id')
                ->where('student_academic_records.academic_year_id', $academicYearId);
        }

        return $this->hasOne(StudentAcademicRecord::class, 'student_id')
            ->join('academic_years', 'student_academic_records.academic_year_id', '=', 'academic_years.id')
            ->where('academic_years.is_current', true)
            ->select('student_academic_records.*');
     }

     public function documents(): HasMany
     {
         return $this->hasMany(StudentDocument::class, 'student_id');
     }
}
