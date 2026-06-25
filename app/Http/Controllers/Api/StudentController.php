<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\School;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Display a listing of students with filters and pagination.
     */
    public function index(Request $request)
    {
        $this->authorize('student.view');

        $currentUser = $request->user();
        
        $query = Student::query()
            ->select(
                'students.*', 
                'sar.roll_no', 
                'sar.academic_year_id',
                'sar.class_id',
                'sar.section_id',
                'classes.name as class_name', 
                'sections.name as section_name', 
                'ay.title as academic_year_title'
            )
            ->join('student_academic_records as sar', 'students.id', '=', 'sar.student_id')
            ->join('classes', 'sar.class_id', '=', 'classes.id')
            ->join('sections', 'sar.section_id', '=', 'sections.id')
            ->join('academic_years as ay', 'sar.academic_year_id', '=', 'ay.id')
            ->with(['parent']);

        // Scope to school
        if (!$currentUser->isSuperAdmin()) {
            $query->where('students.school_id', $currentUser->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('students.school_id', $request->input('school_id'));
        }

        // Apply filters
        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId && !$request->filled('class_id') && !$request->filled('section_id') && !$currentUser->isSuperAdmin()) {
            $activeYear = AcademicYear::where('school_id', $currentUser->school_id)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->first();
            if ($activeYear) {
                $academicYearId = $activeYear->id;
            }
        }

        if ($academicYearId) {
            $query->where('sar.academic_year_id', $academicYearId);
        }
        if ($request->filled('class_id')) {
            $query->where('sar.class_id', $request->input('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('sar.section_id', $request->input('section_id'));
        }
        if ($request->filled('status')) {
            $query->where('students.status', $request->input('status'));
        } else {
            $query->where('students.status', 'active');
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'is_delete')) {
            $query->where('students.is_delete', 0);
        }
        $query->where('classes.status', 'active')
            ->where('classes.is_delete', 0)
            ->where('sections.status', 'active')
            ->where('sections.is_delete', 0)
            ->where('ay.status', 'active')
            ->where('ay.is_delete', 0);
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('students.first_name', 'like', "%{$search}%")
                  ->orWhere('students.last_name', 'like', "%{$search}%")
                  ->orWhere('students.admission_no', 'like', "%{$search}%")
                  ->orWhere('students.mobile', 'like', "%{$search}%");
            });
        }

        // Check if CSV export is requested
        if ($request->input('export') === 'csv') {
            return $this->exportCSV($query->get());
        }

        $students = $query->orderBy('students.id', 'desc')
            ->paginate($request->input('per_page', 10));

        return response()->json($students);
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('student.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $student = $this->studentService->createStudent($request->validated(), $schoolId);

        return response()->json([
            'message' => 'Student admitted successfully',
            'student' => $student->load(['parent', 'academicRecords.class', 'academicRecords.section'])
        ], 201);
    }

    /**
     * Display the specified student profile.
     */
    public function show(Student $student, Request $request): JsonResponse
    {
        $this->authorize('student.view');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $student->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Load personal info, parent info, and full academic history sorted by year
        $student->load([
            'parent',
            'academicRecords' => function ($q) {
                $q->join('academic_years', 'student_academic_records.academic_year_id', '=', 'academic_years.id')
                  ->orderBy('academic_years.start_date', 'desc')
                  ->select('student_academic_records.*')
                  ->with(['academicYear', 'class', 'section']);
            }
        ]);

        return response()->json([
            'student' => $student
        ]);
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorize('student.edit');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $student->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $updated = $this->studentService->updateStudent($student, $request->validated());

        return response()->json([
            'message' => 'Student updated successfully',
            'student' => $updated->load(['parent', 'academicRecords.class', 'academicRecords.section'])
        ]);
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student, Request $request): JsonResponse
    {
        $this->authorize('student.delete');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $student->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $student->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Helper to export student listing as CSV.
     */
    protected function exportCSV($students): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_list.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($students) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'Admission No',
                'First Name',
                'Last Name',
                'Gender',
                'Date of Birth',
                'Academic Year',
                'Class',
                'Section',
                'Roll No',
                'Mobile',
                'Email',
                'Status'
            ]);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->admission_no,
                    $student->first_name,
                    $student->last_name,
                    $student->gender,
                    $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '',
                    $student->academic_year_title,
                    $student->class_name,
                    $student->section_name,
                    $student->roll_no,
                    $student->mobile,
                    $student->email,
                    $student->status
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Download a sample import CSV file with 2 records.
     */
    public function demoCSV(Request $request): StreamedResponse
    {
        $this->authorize('student.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin() ? School::first()?->id : $currentUser->school_id;

        // Find a valid academic year, class, and section for the school to guide the user
        $academicYear = AcademicYear::where('school_id', $schoolId)->where('is_current', true)->first() 
            ?? AcademicYear::where('school_id', $schoolId)->first();
        $class = ClassModel::where('school_id', $schoolId)->first();
        $section = $class ? Section::where('class_id', $class->id)->first() : null;

        $ayTitle = $academicYear ? $academicYear->title : '2025-2026';
        $className = $class ? $class->name : 'Class 1';
        $sectionName = $section ? $section->name : 'A';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_demo.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($ayTitle, $className, $sectionName) {
            $handle = fopen('php://output', 'w');

            // Header columns
            fputcsv($handle, [
                'admission_no',
                'first_name',
                'last_name',
                'gender',
                'date_of_birth',
                'admission_date',
                'blood_group',
                'roll_no',
                'academic_year',
                'class',
                'section',
                'mobile',
                'email',
                'father_name',
                'father_mobile',
                'father_email',
                'mother_name',
                'mother_mobile',
                'mother_email',
                'guardian_name',
                'guardian_mobile',
                'address'
            ]);

            // Sample row 1
            fputcsv($handle, [
                'ADM-' . date('Y') . '-0001',
                'John',
                'Doe',
                'Male',
                '2015-05-15',
                date('Y-m-d'),
                'A+',
                '01',
                $ayTitle,
                $className,
                $sectionName,
                '5550101',
                'john.doe@example.com',
                'Richard Doe',
                '5550102',
                'richard.doe@example.com',
                'Mary Doe',
                '5550103',
                'mary.doe@example.com',
                '',
                '',
                '123 School Lane'
            ]);

            // Sample row 2
            fputcsv($handle, [
                'ADM-' . date('Y') . '-0002',
                'Jane',
                'Smith',
                'Female',
                '2016-08-20',
                date('Y-m-d'),
                'B+',
                '02',
                $ayTitle,
                $className,
                $sectionName,
                '5550201',
                'jane.smith@example.com',
                'Robert Smith',
                '5550202',
                'robert.smith@example.com',
                'Sarah Smith',
                '5550203',
                'sarah.smith@example.com',
                '',
                '',
                '456 Learning Blvd'
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Import students from uploaded CSV file.
     */
    public function importCSV(Request $request): JsonResponse
    {
        $this->authorize('student.create');

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:4096'
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin() ? $request->input('school_id') : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();
        
        $handle = fopen($path, 'r');
        if (!$handle) {
            return response()->json(['message' => 'Failed to open the uploaded file.'], 422);
        }

        // Read headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return response()->json(['message' => 'The uploaded file is empty.'], 422);
        }

        // Clean headers: trim and normalize
        $headers = array_map(function($h) {
            return strtolower(trim(str_replace(["\xEF\xBB\xBF", '"', "'"], '', $h)));
        }, $headers);

        // Map column indexes based on header names
        $expectedColumns = [
            'admission_no' => 0,
            'first_name' => 1,
            'last_name' => 2,
            'gender' => 3,
            'date_of_birth' => 4,
            'admission_date' => 5,
            'roll_no' => 6,
            'academic_year' => 7,
            'class' => 8,
            'section' => 9,
            'mobile' => 10,
            'email' => 11,
            'father_name' => 12,
            'father_mobile' => 13,
            'father_email' => 14,
            'mother_name' => 15,
            'mother_mobile' => 16,
            'mother_email' => 17,
            'guardian_name' => 18,
            'guardian_mobile' => 19,
            'blood_group' => 21
        ];

        $columnMap = [];
        foreach ($expectedColumns as $key => $defaultIdx) {
            $idx = array_search($key, $headers);
            $columnMap[$key] = ($idx !== false) ? $idx : $defaultIdx;
        }

        $rowNum = 1;
        $successCount = 0;
        $errorsList = [];
        // Track seen identifiers to prevent file-internal duplicates
        $seenAdmissions = [];
        $seenRolls = [];

        // Load existing database records for comparison
        $schoolAcademicYears = AcademicYear::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->get()->keyBy(function($y) {
                return strtolower(trim($y->title));
            });

        $schoolClasses = ClassModel::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->get()->keyBy(function($c) {
                return strtolower(trim($c->name));
            });

        $schoolSections = Section::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->get();

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;

                if (empty(array_filter($row))) {
                    continue;
                }

                $rowErrors = [];

                $val = function($key) use ($row, $columnMap) {
                    if (!isset($columnMap[$key])) {
                        return '';
                    }
                    $idx = $columnMap[$key];
                    return isset($row[$idx]) ? trim($row[$idx]) : '';
                };

                $admission_no = $val('admission_no');
                $first_name = $val('first_name');
                $last_name = $val('last_name');
                $gender = $val('gender');
                $date_of_birth = $val('date_of_birth');
                $admission_date = $val('admission_date');
                $roll_no = $val('roll_no');
                $academic_year_title = $val('academic_year');
                $class_name = $val('class');
                $section_name = $val('section');
                
                $mobile = $val('mobile');
                $email = $val('email');
                $father_name = $val('father_name');
                $father_mobile = $val('father_mobile');
                $father_email = $val('father_email');
                $mother_name = $val('mother_name');
                $mother_mobile = $val('mother_mobile');
                $mother_email = $val('mother_email');
                $guardian_name = $val('guardian_name');
                $guardian_mobile = $val('guardian_mobile');
                $address = $val('address');

                if (empty($admission_no)) {
                    $rowErrors[] = "Admission number is required.";
                }
                if (empty($first_name)) {
                    $rowErrors[] = "First name is required.";
                }
                if (empty($last_name)) {
                    $rowErrors[] = "Last name is required.";
                }
                if (empty($gender)) {
                    $rowErrors[] = "Gender is required.";
                } elseif (!in_array(strtolower($gender), ['male', 'female', 'other'])) {
                    $rowErrors[] = "Gender must be Male, Female, or Other.";
                } else {
                    $gender = ucfirst(strtolower($gender));
                }

                if (empty($date_of_birth)) {
                    $rowErrors[] = "Date of birth is required.";
                } elseif (!strtotime($date_of_birth)) {
                    $rowErrors[] = "Date of birth format is invalid (use YYYY-MM-DD).";
                }

                if (empty($admission_date)) {
                    $rowErrors[] = "Admission date is required.";
                } elseif (!strtotime($admission_date)) {
                    $rowErrors[] = "Admission date format is invalid (use YYYY-MM-DD).";
                }

                if (empty($academic_year_title)) {
                    $rowErrors[] = "Academic Year title is required.";
                }
                if (empty($class_name)) {
                    $rowErrors[] = "Class name is required.";
                }
                if (empty($section_name)) {
                    $rowErrors[] = "Section name is required.";
                }

                $ayId = null;
                $classId = null;
                $sectionId = null;

                if (empty($rowErrors)) {
                    $ayKey = strtolower(trim($academic_year_title));
                    if (!$schoolAcademicYears->has($ayKey)) {
                        $rowErrors[] = "Academic Year '{$academic_year_title}' does not exist in this school.";
                    } else {
                        $ayId = $schoolAcademicYears->get($ayKey)->id;
                    }

                    $classKey = strtolower(trim($class_name));
                    if (!$schoolClasses->has($classKey)) {
                        $rowErrors[] = "Class '{$class_name}' does not exist in this school.";
                    } else {
                        $classId = $schoolClasses->get($classKey)->id;
                    }

                    if ($classId) {
                        $sectKey = strtolower(trim($section_name));
                        $sect = $schoolSections->first(function($s) use ($classId, $sectKey) {
                            return $s->class_id === $classId && strtolower(trim($s->name)) === $sectKey;
                        });

                        if (!$sect) {
                            $rowErrors[] = "Section '{$section_name}' does not exist under class '{$class_name}'.";
                        } else {
                            $sectionId = $sect->id;
                        }
                    }
                }

                if (empty($rowErrors)) {
                    if (in_array($admission_no, $seenAdmissions)) {
                        $rowErrors[] = "Duplicate Admission No '{$admission_no}' found within this import file.";
                    } else {
                        $seenAdmissions[] = $admission_no;
                    }

                    $existsInDb = Student::where('school_id', $schoolId)
                        ->where('admission_no', $admission_no)
                        ->where('is_delete', 0)
                        ->exists();
                    if ($existsInDb) {
                        $rowErrors[] = "Admission No '{$admission_no}' already exists in this school.";
                    }

                    if (!empty($roll_no) && $ayId && $classId && $sectionId) {
                        $rollKey = "{$ayId}-{$classId}-{$sectionId}-{$roll_no}";

                        if (in_array($rollKey, $seenRolls)) {
                            $rowErrors[] = "Duplicate Roll No '{$roll_no}' for same academic year/class/section within this import file.";
                        } else {
                            $seenRolls[] = $rollKey;
                        }

                        $rollExistsInDb = StudentAcademicRecord::where('student_academic_records.school_id', $schoolId)
                            ->join('students', 'student_academic_records.student_id', '=', 'students.id')
                            ->where('students.is_delete', 0)
                            ->where('student_academic_records.academic_year_id', $ayId)
                            ->where('student_academic_records.class_id', $classId)
                            ->where('student_academic_records.section_id', $sectionId)
                            ->where('student_academic_records.roll_no', $roll_no)
                            ->exists();
                        if ($rollExistsInDb) {
                            $rowErrors[] = "Roll No '{$roll_no}' already exists in this class/section.";
                        }
                    }
                }

                if (!empty($rowErrors)) {
                    $errorsList[] = [
                        'row' => $rowNum,
                        'admission_no' => $admission_no ?: 'N/A',
                        'name' => ($first_name || $last_name) ? "{$first_name} {$last_name}" : 'N/A',
                        'errors' => $rowErrors
                    ];
                    continue;
                }

                $studentData = [
                    'admission_no' => $admission_no,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'gender' => $gender,
                    'date_of_birth' => $date_of_birth,
                    'admission_date' => $admission_date,
                    'blood_group' => $val('blood_group'),
                    'mobile' => $mobile,
                    'email' => $email,
                    'address' => $address,
                    'status' => 'active',
                    'father_name' => $father_name,
                    'father_mobile' => $father_mobile,
                    'father_email' => $father_email,
                    'mother_name' => $mother_name,
                    'mother_mobile' => $mother_mobile,
                    'mother_email' => $mother_email,
                    'guardian_name' => $guardian_name,
                    'guardian_mobile' => $guardian_mobile,
                    'academic_year_id' => $ayId,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'roll_no' => $roll_no,
                ];

                $this->studentService->createStudent($studentData, $schoolId);
                $successCount++;
            }

            fclose($handle);

            if (!empty($errorsList)) {
                \Illuminate\Support\Facades\DB::rollBack();
                return response()->json([
                    'message' => 'Some rows failed validation. No data has been imported.',
                    'success_count' => 0,
                    'errors' => $errorsList
                ], 422);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => "Successfully imported {$successCount} students.",
                'success_count' => $successCount,
                'errors' => []
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            fclose($handle);
            return response()->json([
                'message' => 'An error occurred during import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload student photo and save it school/class/section wise.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_id' => 'nullable|integer',
            'class_id' => 'nullable',
            'section_id' => 'nullable',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? ($request->input('school_id') ?: 1)
            : $currentUser->school_id;

        $classId = $request->input('class_id') ?: 'unassigned';
        $sectionId = $request->input('section_id') ?: 'unassigned';

        $file = $request->file('photo');
        
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '_', $originalName);
        $filename = time() . '_' . $cleanName . '.' . $file->getClientOriginalExtension();
        
        $destinationPath = public_path("uploads/students/{$schoolId}/{$classId}/{$sectionId}");
        
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        
        $file->move($destinationPath, $filename);
        
        $url = url("uploads/students/{$schoolId}/{$classId}/{$sectionId}/{$filename}");
        
        return response()->json([
            'url' => $url,
            'path' => "uploads/students/{$schoolId}/{$classId}/{$sectionId}/{$filename}"
        ], 200);
    }
}
