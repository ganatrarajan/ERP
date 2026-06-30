<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentParent;
use App\Models\StudentAcademicRecord;
use Illuminate\Support\Facades\DB;

class StudentService
{
    /**
     * Create a student, parent record, and current academic record in a transaction.
     */
    public function createStudent(array $data, int $schoolId): Student
    {
        return DB::transaction(function () use ($data, $schoolId) {
            $photo = $this->relocatePhoto(
                $data['photo'] ?? null,
                $schoolId,
                $data['class_id'],
                $data['section_id']
            );

            // Step 1: Create student master record
            $student = Student::create([
                'school_id' => $schoolId,
                'admission_no' => $data['admission_no'],
                'gr_no' => $data['gr_no'] ?? null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'category' => $data['category'] ?? null,
                'religion' => $data['religion'] ?? null,
                'nationality' => $data['nationality'] ?? 'Indian',
                'aadhaar_no' => $data['aadhaar_no'] ?? null,
                'pen_no' => $data['pen_no'] ?? null,
                'udise_no' => $data['udise_no'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'blood_group' => $data['blood_group'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'previous_school_name' => $data['previous_school_name'] ?? null,
                'previous_school_tc_no' => $data['previous_school_tc_no'] ?? null,
                'previous_school_tc_date' => $data['previous_school_tc_date'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_mobile' => $data['emergency_contact_mobile'] ?? null,
                'emergency_contact_email' => $data['emergency_contact_email'] ?? null,
                'photo' => $photo,
                'admission_date' => $data['admission_date'],
                'status' => $data['status'] ?? 'active',
                'house' => $data['house'] ?? null,
            ]);

            // Step 2: Create parent record
            StudentParent::create([
                'student_id' => $student->id,
                'father_name' => $data['father_name'] ?? null,
                'father_mobile' => $data['father_mobile'] ?? null,
                'father_email' => $data['father_email'] ?? null,
                'mother_name' => $data['mother_name'] ?? null,
                'mother_mobile' => $data['mother_mobile'] ?? null,
                'mother_email' => $data['mother_email'] ?? null,
                'guardian_name' => $data['guardian_name'] ?? null,
                'guardian_mobile' => $data['guardian_mobile'] ?? null,
            ]);

            // Step 3: Create academic record
            StudentAcademicRecord::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'academic_year_id' => $data['academic_year_id'],
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'roll_no' => $data['roll_no'] ?? null,
                'status' => 'active',
            ]);

            // Step 4: Apply existing active holidays to new student
            \App\Services\HolidayService::applyHolidaysToNewStudent(
                $schoolId, 
                $data['academic_year_id'], 
                $student->id, 
                $data['class_id'], 
                $data['section_id']
            );

            return $student;
        });
    }

    /**
     * Update a student, parent, and current/specific academic year record.
     */
    public function updateStudent(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            $photo = $this->relocatePhoto(
                $data['photo'] ?? null,
                $student->school_id,
                $data['class_id'],
                $data['section_id']
            );

            // Update student master record
            $student->update([
                'admission_no' => $data['admission_no'],
                'gr_no' => $data['gr_no'] ?? null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'category' => $data['category'] ?? null,
                'religion' => $data['religion'] ?? null,
                'nationality' => $data['nationality'] ?? 'Indian',
                'aadhaar_no' => $data['aadhaar_no'] ?? null,
                'pen_no' => $data['pen_no'] ?? null,
                'udise_no' => $data['udise_no'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'blood_group' => $data['blood_group'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'previous_school_name' => $data['previous_school_name'] ?? null,
                'previous_school_tc_no' => $data['previous_school_tc_no'] ?? null,
                'previous_school_tc_date' => $data['previous_school_tc_date'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_mobile' => $data['emergency_contact_mobile'] ?? null,
                'emergency_contact_email' => $data['emergency_contact_email'] ?? null,
                'photo' => $photo,
                'admission_date' => $data['admission_date'],
                'status' => $data['status'] ?? $student->status,
                'house' => $data['house'] ?? null,
            ]);

            // Update parent record
            if ($student->parent) {
                $student->parent->update([
                    'father_name' => $data['father_name'] ?? null,
                    'father_mobile' => $data['father_mobile'] ?? null,
                    'father_email' => $data['father_email'] ?? null,
                    'mother_name' => $data['mother_name'] ?? null,
                    'mother_mobile' => $data['mother_mobile'] ?? null,
                    'mother_email' => $data['mother_email'] ?? null,
                    'guardian_name' => $data['guardian_name'] ?? null,
                    'guardian_mobile' => $data['guardian_mobile'] ?? null,
                ]);
            } else {
                StudentParent::create([
                    'student_id' => $student->id,
                    'father_name' => $data['father_name'] ?? null,
                    'father_mobile' => $data['father_mobile'] ?? null,
                    'father_email' => $data['father_email'] ?? null,
                    'mother_name' => $data['mother_name'] ?? null,
                    'mother_mobile' => $data['mother_mobile'] ?? null,
                    'mother_email' => $data['mother_email'] ?? null,
                    'guardian_name' => $data['guardian_name'] ?? null,
                    'guardian_mobile' => $data['guardian_mobile'] ?? null,
                ]);
            }

            // Update or create academic record for the selected academic year
            if (isset($data['academic_year_id'])) {
                StudentAcademicRecord::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $data['academic_year_id']
                    ],
                    [
                        'school_id' => $student->school_id,
                        'class_id' => $data['class_id'],
                        'section_id' => $data['section_id'],
                        'roll_no' => $data['roll_no'] ?? null,
                        'status' => 'active',
                    ]
                );

                // Apply existing active holidays to student for this academic year
                \App\Services\HolidayService::applyHolidaysToNewStudent(
                    $student->school_id, 
                    $data['academic_year_id'], 
                    $student->id, 
                    $data['class_id'], 
                    $data['section_id']
                );
            }

            return $student;
        });
    }

    /**
     * Relocate a student photo from unassigned folder to class/section folder.
     */
    protected function relocatePhoto(?string $photo, int $schoolId, int $classId, int $sectionId): ?string
    {
        if ($photo && str_contains($photo, '/unassigned/')) {
            $oldRelativePath = parse_url($photo, PHP_URL_PATH);
            $oldRelativePath = ltrim($oldRelativePath, '/');
            if ($oldRelativePath && file_exists(public_path($oldRelativePath))) {
                $filename = basename($oldRelativePath);
                $newRelativeDir = "uploads/students/{$schoolId}/{$classId}/{$sectionId}";
                $newRelativePath = "{$newRelativeDir}/{$filename}";
                $newFullDir = public_path($newRelativeDir);
                if (!file_exists($newFullDir)) {
                    mkdir($newFullDir, 0755, true);
                }
                if (rename(public_path($oldRelativePath), public_path($newRelativePath))) {
                    return url($newRelativePath);
                }
            }
        }
        return $photo;
    }
}
