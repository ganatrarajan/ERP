<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'school_code',
        'email',
        'phone',
        'logo',
        'address',
        'status',
        'default_report_card_template',
        'mobile_academic_year_id',
    ];

    /**
     * Get the mobile academic year of the school.
     */
    public function mobileAcademicYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'mobile_academic_year_id');
    }

    /**
     * Get the users belonging to the school.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the modules associated with this school.
     */
    public function modules(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'school_modules')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Get the school module Pivot records.
     */
    public function schoolModules(): HasMany
    {
        return $this->hasMany(SchoolModule::class);
    }

    protected static function booted()
    {
        static::creating(function ($school) {
            do {
                $code = (string) random_int(10000, 99999);
            } while (static::where('school_code', $code)->exists());
            
            $school->school_code = $code;
        });

        static::created(function ($school) {
            // Create default roles for this school
            $schoolAdminRole = \App\Models\Role::firstOrCreate([
                'name' => 'School Admin',
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            $schoolAdminRole->syncPermissions([
                'dashboard.view',
                'user.view',
                'user.create',
                'user.edit',
                'user.delete',
                'role.view',
                'role.edit',
                'permission.view',
                'permission.edit',
                'settings.view',
                'settings.edit',
                // Academic and student
                'academic_year.view',
                'academic_year.create',
                'academic_year.edit',
                'academic_year.delete',
                'class.view',
                'class.create',
                'class.edit',
                'class.delete',
                'section.view',
                'section.create',
                'section.edit',
                'section.delete',
                'student.view',
                'student.create',
                'student.edit',
                'student.delete',
                'promotion.view',
                'promotion.create',
                // subjects
                'subject.view',
                'subject.create',
                'subject.edit',
                'subject.delete',
                // attendance
                'attendance.view',
                'attendance.create',
                'attendance.edit',
                'attendance.delete',
                // homework
                'homework.view',
                'homework.create',
                'homework.edit',
                'homework.delete',
                // notices
                'notice.view',
                'notice.create',
                'notice.edit',
                'notice.delete',
                // examinations
                'exam.view',
                'exam.create',
                'exam.edit',
                'exam.delete',
                'exam_schedule.view',
                'exam_schedule.create',
                'exam_schedule.edit',
                'exam_schedule.delete',
                'marks.view',
                'marks.create',
                'marks.edit',
                'result.view',
                'report_card.view',
                // fees
                'fee_type.view',
                'fee_type.create',
                'fee_type.edit',
                'fee_type.delete',
                'fee_structure.view',
                'fee_structure.create',
                'fee_structure.edit',
                'fee_structure.delete',
                'fee_collection.view',
                'fee_collection.create',
                'fee_collection.edit',
                'receipt.view',
                'ledger.view',
                'report.view',
            ]);

            \App\Models\Role::firstOrCreate([
                'name' => 'Teacher',
                'guard_name' => 'web',
                'school_id' => $school->id,
            ])->syncPermissions([
                'dashboard.view',
                'academic_year.view',
                'class.view',
                'section.view',
                'student.view',
            ]);
        });
    }
}
