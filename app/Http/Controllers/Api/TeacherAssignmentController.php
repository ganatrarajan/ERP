<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    /**
     * Display a listing of teacher assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('user.view');
        $currentUser = $request->user();

        $query = TeacherAssignment::with(['teacher', 'academicYear', 'class', 'section', 'subject']);

        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        return response()->json([
            'assignments' => $query->get()
        ]);
    }

    /**
     * Store a newly created teacher assignment.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('user.edit');
        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? ($request->input('school_id') ?: 1)
            : $currentUser->school_id;

        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'is_class_teacher' => 'boolean',
        ]);

        $teacherId = $request->input('teacher_id');
        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $subjectId = $request->input('subject_id');
        $isClassTeacher = $request->boolean('is_class_teacher');

        // Check if teacher is indeed a Teacher
        $teacher = User::findOrFail($teacherId);
        if (!$currentUser->isSuperAdmin() && $teacher->school_id !== $schoolId) {
            return response()->json(['message' => 'Unauthorized operation.'], 403);
        }

        if (!$teacher->hasRole('Teacher')) {
            return response()->json(['message' => 'The selected user is not a Teacher.'], 422);
        }

        // If section is not chosen, assign to all sections of the class
        if (empty($sectionId)) {
            if (!$isClassTeacher) {
                return response()->json(['message' => 'Please select a section, or designate as Class Teacher to assign to all sections.'], 422);
            }

            // Find all sections for this class
            $sections = Section::where('class_id', $classId)->get();
            if ($sections->isEmpty()) {
                return response()->json(['message' => 'No sections found for the selected class.'], 422);
            }

            $createdCount = 0;
            $errors = [];

            foreach ($sections as $sec) {
                // Check if this teacher is already designated as class teacher for this class/section
                $alreadyClassTeacher = TeacherAssignment::where('academic_year_id', $academicYearId)
                    ->where('class_id', $classId)
                    ->where('section_id', $sec->id)
                    ->where('teacher_id', $teacherId)
                    ->where('is_class_teacher', true)
                    ->exists();

                if ($alreadyClassTeacher) {
                    continue;
                }

                // Check if another teacher is already the class teacher for this section
                $existingClassTeacher = TeacherAssignment::where('academic_year_id', $academicYearId)
                    ->where('class_id', $classId)
                    ->where('section_id', $sec->id)
                    ->where('is_class_teacher', true)
                    ->where('teacher_id', '!=', $teacherId)
                    ->first();

                if ($existingClassTeacher) {
                    $errors[] = "Section {$sec->name} already has a designated Class Teacher: {$existingClassTeacher->teacher->name}.";
                    continue;
                }

                // Create assignment for this section
                TeacherAssignment::create([
                    'school_id' => $schoolId,
                    'teacher_id' => $teacherId,
                    'academic_year_id' => $academicYearId,
                    'class_id' => $classId,
                    'section_id' => $sec->id,
                    'subject_id' => $subjectId,
                    'is_class_teacher' => $isClassTeacher,
                ]);
                $createdCount++;
            }

            if ($createdCount === 0) {
                if (!empty($errors)) {
                    return response()->json(['message' => implode(' ', $errors)], 422);
                }
                return response()->json(['message' => 'This teacher is already designated as Class Teacher for all sections of this class.'], 422);
            }

            return response()->json([
                'message' => "Teacher successfully designated as Class Teacher for {$createdCount} sections." . (empty($errors) ? '' : ' ' . implode(' ', $errors)),
            ], 201);
        }

        // Check section belongs to class
        $section = Section::findOrFail($sectionId);
        if ($section->class_id !== (int)$classId) {
            return response()->json(['message' => 'The selected section does not belong to the selected class.'], 422);
        }

        // Check subject belongs to class and section if provided
        if ($subjectId) {
            $subject = Subject::findOrFail($subjectId);
            if ($subject->class_id !== (int)$classId || ($subject->section_id && $subject->section_id !== (int)$sectionId)) {
                return response()->json(['message' => 'The selected subject does not belong to this class or section.'], 422);
            }
        }

        // Check if assignment already exists
        $existing = TeacherAssignment::where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'This assignment already exists for this teacher.'], 422);
        }

        // If assigning class teacher, check if another teacher is already the class teacher
        if ($isClassTeacher) {
            // First check if THIS teacher is already designated as class teacher for this class/section
            $alreadyClassTeacher = TeacherAssignment::where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('teacher_id', $teacherId)
                ->where('is_class_teacher', true)
                ->exists();

            if ($alreadyClassTeacher) {
                return response()->json([
                    'message' => 'This teacher is already designated as the Class Teacher for this class and section.'
                ], 422);
            }

            $existingClassTeacher = TeacherAssignment::where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('is_class_teacher', true)
                ->where('teacher_id', '!=', $teacherId)
                ->first();

            if ($existingClassTeacher) {
                return response()->json([
                    'message' => 'This class and section already has a designated Class Teacher: ' . $existingClassTeacher->teacher->name
                ], 422);
            }
        }

        // Create assignment
        $assignment = TeacherAssignment::create([
            'school_id' => $schoolId,
            'teacher_id' => $teacherId,
            'academic_year_id' => $academicYearId,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'subject_id' => $subjectId,
            'is_class_teacher' => $isClassTeacher,
        ]);

        return response()->json([
            'message' => 'Teacher assigned successfully.',
            'assignment' => $assignment->load(['teacher', 'academicYear', 'class', 'section', 'subject'])
        ], 201);
    }

    /**
     * Remove the specified assignment.
     */
    public function destroy(TeacherAssignment $teacherAssignment, Request $request): JsonResponse
    {
        $this->authorize('user.edit');
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin() && $teacherAssignment->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $teacherAssignment->delete();

        return response()->json([
            'message' => 'Assignment removed successfully.'
        ]);
    }

    /**
     * Update the specified assignment.
     */
    public function update(Request $request, TeacherAssignment $teacherAssignment): JsonResponse
    {
        $this->authorize('user.edit');
        $currentUser = $request->user();
        
        if (!$currentUser->isSuperAdmin() && $teacherAssignment->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'is_class_teacher' => 'boolean',
        ]);

        $teacherId = $request->input('teacher_id');
        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $subjectId = $request->input('subject_id');
        $isClassTeacher = $request->boolean('is_class_teacher');

        // Check if teacher is indeed a Teacher
        $teacher = User::findOrFail($teacherId);
        if (!$currentUser->isSuperAdmin() && $teacher->school_id !== $teacherAssignment->school_id) {
            return response()->json(['message' => 'Unauthorized operation.'], 403);
        }

        if (!$teacher->hasRole('Teacher')) {
            return response()->json(['message' => 'The selected user is not a Teacher.'], 422);
        }

        // Check duplicate assignments (excluding current ID)
        $existing = TeacherAssignment::where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->where('id', '!=', $teacherAssignment->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'This assignment already exists for this teacher.'], 422);
        }

        // If assigning class teacher, check if another teacher is already the class teacher
        if ($isClassTeacher) {
            $existingClassTeacher = TeacherAssignment::where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('is_class_teacher', true)
                ->where('teacher_id', '!=', $teacherId)
                ->where('id', '!=', $teacherAssignment->id)
                ->first();

            if ($existingClassTeacher) {
                return response()->json([
                    'message' => 'This class and section already has a designated Class Teacher: ' . $existingClassTeacher->teacher->name
                ], 422);
            }
        }

        $teacherAssignment->update([
            'teacher_id' => $teacherId,
            'academic_year_id' => $academicYearId,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'subject_id' => $subjectId,
            'is_class_teacher' => $isClassTeacher,
        ]);

        return response()->json([
            'message' => 'Assignment updated successfully.',
            'assignment' => $teacherAssignment->load(['teacher', 'academicYear', 'class', 'section', 'subject'])
        ]);
    }

    /**
     * Store multiple teacher assignments in bulk.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $this->authorize('user.edit');
        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? ($request->input('school_id') ?: 1)
            : $currentUser->school_id;

        $request->validate([
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_ids' => 'required|array',
            'class_ids.*' => 'exists:classes,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
            'is_class_teacher' => 'boolean',
        ]);

        $teacherIds = $request->input('teacher_ids');
        $academicYearId = $request->input('academic_year_id');
        $classIds = $request->input('class_ids');
        $sectionIds = $request->input('section_ids') ?: [];
        $subjectIds = $request->input('subject_ids') ?: [null];
        $isClassTeacher = $request->boolean('is_class_teacher');

        $createdCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($teacherIds as $teacherId) {
            $teacher = User::findOrFail($teacherId);
            if (!$teacher->hasRole('Teacher')) {
                $errors[] = "User {$teacher->name} is not a Teacher.";
                $skippedCount++;
                continue;
            }

            foreach ($classIds as $classId) {
                // If section_ids is empty, assign to all sections in class
                $resolvedSectionIds = $sectionIds;
                if (empty($resolvedSectionIds)) {
                    $resolvedSectionIds = Section::where('class_id', $classId)->pluck('id')->toArray();
                }

                if (empty($resolvedSectionIds)) {
                    $errors[] = "No sections found for Class ID {$classId}.";
                    $skippedCount++;
                    continue;
                }

                foreach ($resolvedSectionIds as $sectionId) {
                    $section = Section::find($sectionId);
                    if (!$section || $section->class_id !== (int)$classId) {
                        $errors[] = "Section {$sectionId} does not belong to Class {$classId}.";
                        $skippedCount++;
                        continue;
                    }

                    foreach ($subjectIds as $subjectId) {
                        if ($subjectId) {
                            $subject = Subject::find($subjectId);
                            if (!$subject || $subject->class_id !== (int)$classId) {
                                $errors[] = "Subject ID {$subjectId} does not belong to Class {$classId}.";
                                $skippedCount++;
                                continue;
                            }
                        }

                        // Check if assignment already exists
                        $existing = TeacherAssignment::where('teacher_id', $teacherId)
                            ->where('academic_year_id', $academicYearId)
                            ->where('class_id', $classId)
                            ->where('section_id', $sectionId)
                            ->where('subject_id', $subjectId)
                            ->exists();

                        if ($existing) {
                            $className = ClassModel::find($classId)->name ?? 'Class';
                            $subjName = $subjectId ? (Subject::find($subjectId)->name ?? 'Subject') : 'Class Teacher role';
                            $errors[] = "{$teacher->name} is already assigned to {$className} - {$section->name} for {$subjName}.";
                            $skippedCount++;
                            continue;
                        }

                        // Check Class Teacher constraints
                        if ($isClassTeacher) {
                            $existingClassTeacher = TeacherAssignment::where('academic_year_id', $academicYearId)
                                ->where('class_id', $classId)
                                ->where('section_id', $sectionId)
                                ->where('is_class_teacher', true)
                                ->where('teacher_id', '!=', $teacherId)
                                ->first();

                            if ($existingClassTeacher) {
                                $errors[] = "Class Section {$section->name} already has a designated Class Teacher: {$existingClassTeacher->teacher->name}.";
                                $skippedCount++;
                                continue;
                            }
                        }

                        TeacherAssignment::create([
                            'school_id' => $schoolId,
                            'teacher_id' => $teacherId,
                            'academic_year_id' => $academicYearId,
                            'class_id' => $classId,
                            'section_id' => $sectionId,
                            'subject_id' => $subjectId,
                            'is_class_teacher' => $isClassTeacher,
                        ]);
                        $createdCount++;
                    }
                }
            }
        }

        return response()->json([
            'message' => "Bulk assignments processed. Created {$createdCount} records, skipped {$skippedCount}.",
            'created' => $createdCount,
            'skipped' => $skippedCount,
            'errors' => $errors
        ], 201);
    }

    /**
     * Remove multiple assignments in bulk.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('user.edit');
        $currentUser = $request->user();

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:teacher_assignments,id'
        ]);

        $ids = $request->input('ids');
        $deletedCount = 0;

        foreach ($ids as $id) {
            $assignment = TeacherAssignment::find($id);
            if ($assignment) {
                if (!$currentUser->isSuperAdmin() && $assignment->school_id !== $currentUser->school_id) {
                    continue;
                }
                $assignment->delete();
                $deletedCount++;
            }
        }

        return response()->json([
            'message' => "Successfully removed {$deletedCount} teacher assignments.",
            'deleted' => $deletedCount
        ]);
    }

    /**
     * Download assignments list as PDF.
     */
    public function downloadPdf(Request $request)
    {
        $this->authorize('user.view');
        $currentUser = $request->user();

        $query = TeacherAssignment::with(['teacher', 'academicYear', 'class', 'section', 'subject']);

        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        $assignments = $query->get();
        $school = $currentUser->school;

        $selectedKeys = array_filter(explode(',', $request->input('selected_columns', '')));
        if (empty($selectedKeys)) {
            $selectedKeys = ['teacher_name', 'teacher_code', 'academic_session', 'class', 'section', 'subject', 'type'];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.teacher_assignments', [
            'school' => $school,
            'assignments' => $assignments,
            'columns' => $selectedKeys
        ]);

        return $pdf->stream('Teacher_Assignments_Report.pdf');
    }
}
