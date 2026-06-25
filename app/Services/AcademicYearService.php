<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class AcademicYearService
{
    /**
     * Create a new Academic Year.
     */
    public function createAcademicYear(array $data, int $schoolId): AcademicYear
    {
        return DB::transaction(function () use ($data, $schoolId) {
            $isCurrent = !empty($data['is_current']);

            if ($isCurrent) {
                // Deactivate current status for all other academic years in this school
                AcademicYear::where('school_id', $schoolId)->update(['is_current' => false]);
            }

            $academicYear = AcademicYear::create([
                'school_id' => $schoolId,
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => $isCurrent,
                'status' => $data['status'] ?? 'active',
            ]);

            // Handle configuration cloning
            if (!empty($data['clone_source_id'])) {
                $sourceYearId = $data['clone_source_id'];
                $elements = $data['clone_elements'] ?? [];

                // Retrieve source academic year and ensure it belongs to the same school
                $sourceYear = AcademicYear::where('id', $sourceYearId)
                    ->where('school_id', $schoolId)
                    ->where('is_delete', 0)
                    ->firstOrFail();

                $classIdMap = [];
                $sectionIdMap = [];

                $cloneClasses = in_array('classes_sections', $elements);

                // If subjects or fee structures are requested, classes and sections MUST be cloned.
                if ($cloneClasses || in_array('subjects', $elements) || in_array('fee_structures', $elements)) {
                    $oldClasses = \App\Models\ClassModel::where('academic_year_id', $sourceYearId)
                        ->where('school_id', $schoolId)
                        ->where('is_delete', 0)
                        ->get();

                    foreach ($oldClasses as $oldClass) {
                        $newClass = \App\Models\ClassModel::create([
                            'school_id' => $schoolId,
                            'academic_year_id' => $academicYear->id,
                            'name' => $oldClass->name,
                            'description' => $oldClass->description,
                            'status' => $oldClass->status,
                        ]);

                        $classIdMap[$oldClass->id] = $newClass->id;

                        // Clone sections for this class
                        $oldSections = \App\Models\Section::where('class_id', $oldClass->id)
                            ->where('school_id', $schoolId)
                            ->where('is_delete', 0)
                            ->get();

                        foreach ($oldSections as $oldSection) {
                            $newSection = \App\Models\Section::create([
                                'school_id' => $schoolId,
                                'class_id' => $newClass->id,
                                'name' => $oldSection->name,
                                'status' => $oldSection->status,
                            ]);

                            $sectionIdMap[$oldSection->id] = $newSection->id;
                        }
                    }
                }

                // Clone Subjects
                if (in_array('subjects', $elements)) {
                    $oldSubjects = \App\Models\Subject::where('academic_year_id', $sourceYearId)
                        ->where('school_id', $schoolId)
                        ->where('is_delete', 0)
                        ->get();

                    foreach ($oldSubjects as $oldSubject) {
                        $newClassId = null;
                        $newSectionId = null;

                        if ($oldSubject->class_id && isset($classIdMap[$oldSubject->class_id])) {
                            $newClassId = $classIdMap[$oldSubject->class_id];
                        }
                        if ($oldSubject->section_id && isset($sectionIdMap[$oldSubject->section_id])) {
                            $newSectionId = $sectionIdMap[$oldSubject->section_id];
                        }

                        // Skip subject if it's class-bound but class wasn't cloned
                        if ($oldSubject->class_id && !$newClassId) {
                            continue;
                        }

                        \App\Models\Subject::create([
                            'school_id' => $schoolId,
                            'academic_year_id' => $academicYear->id,
                            'class_id' => $newClassId,
                            'section_id' => $newSectionId,
                            'name' => $oldSubject->name,
                            'code' => $oldSubject->code,
                            'description' => $oldSubject->description,
                            'status' => $oldSubject->status,
                            'is_optional' => $oldSubject->is_optional,
                            'evaluation_type' => $oldSubject->evaluation_type,
                            'subject_category' => $oldSubject->subject_category,
                            'maximum_marks' => $oldSubject->maximum_marks,
                            'passing_marks' => $oldSubject->passing_marks,
                            'grade_scale_id' => $oldSubject->grade_scale_id,
                        ]);
                    }
                }

                // Clone Fee Structures (with Items and shifted Installment Dates)
                if (in_array('fee_structures', $elements)) {
                    $oldFeeStructures = \App\Models\FeeStructure::where('academic_year_id', $sourceYearId)
                        ->where('school_id', $schoolId)
                        ->where('is_delete', 0)
                        ->get();

                    $sourceStart = \Carbon\Carbon::parse($sourceYear->start_date);
                    $targetStart = \Carbon\Carbon::parse($academicYear->start_date);
                    $daysDiff = $sourceStart->diffInDays($targetStart, false);

                    foreach ($oldFeeStructures as $oldFeeStructure) {
                        $newClassId = null;
                        if ($oldFeeStructure->class_id && isset($classIdMap[$oldFeeStructure->class_id])) {
                            $newClassId = $classIdMap[$oldFeeStructure->class_id];
                        }

                        // Skip fee structure if it's class-bound but class wasn't cloned
                        if ($oldFeeStructure->class_id && !$newClassId) {
                            continue;
                        }

                        $newFeeStructure = \App\Models\FeeStructure::create([
                            'school_id' => $schoolId,
                            'academic_year_id' => $academicYear->id,
                            'class_id' => $newClassId,
                            'name' => $oldFeeStructure->name,
                            'description' => $oldFeeStructure->description,
                            'status' => $oldFeeStructure->status,
                        ]);

                        // Clone items
                        foreach ($oldFeeStructure->items as $item) {
                            \App\Models\FeeStructureItem::create([
                                'fee_structure_id' => $newFeeStructure->id,
                                'fee_type_id' => $item->fee_type_id,
                                'amount' => $item->amount,
                            ]);
                        }

                        // Clone installments with date shifting
                        foreach ($oldFeeStructure->installments as $installment) {
                            $oldDueDate = \Carbon\Carbon::parse($installment->due_date);
                            $newDueDate = $oldDueDate->copy()->addDays($daysDiff);

                            \App\Models\FeeInstallment::create([
                                'school_id' => $schoolId,
                                'fee_structure_id' => $newFeeStructure->id,
                                'installment_name' => $installment->installment_name,
                                'due_date' => $newDueDate->toDateString(),
                                'amount' => $installment->amount,
                                'sort_order' => $installment->sort_order,
                                'status' => $installment->status,
                            ]);
                        }
                    }
                }
            }

            return $academicYear;
        });
    }

    /**
     * Update an Academic Year.
     */
    public function updateAcademicYear(AcademicYear $academicYear, array $data): AcademicYear
    {
        return DB::transaction(function () use ($academicYear, $data) {
            $isCurrent = !empty($data['is_current']);

            if ($isCurrent) {
                // Deactivate current status for all other academic years in this school
                AcademicYear::where('school_id', $academicYear->school_id)
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_current' => false]);
            }

            $academicYear->update([
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => $isCurrent,
                'status' => $data['status'] ?? $academicYear->status,
            ]);

            return $academicYear;
        });
    }
}
