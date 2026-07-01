<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Role;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('user.view');

        $currentUser = $request->user();
        $query = User::with(['school', 'roles', 'assignments.class', 'assignments.section'])->withCount('documents');

        // Scope to own school if not Super Admin
        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        }

        // Apply filters
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->input('role'))
                  ->orWhere('id', $request->input('role'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        if ($request->filled('designation')) {
            $query->where('designation', $request->input('designation'));
        }

        $hasAssignmentFilter = $request->filled('class_id') || 
                               $request->filled('section_id') || 
                               $request->filled('subject_id') || 
                               $request->boolean('is_class_teacher');

        if ($hasAssignmentFilter) {
            $query->whereIn('id', function($q) use ($request) {
                $q->select('teacher_id')
                  ->from('teacher_assignments');
                if ($request->filled('class_id')) {
                    $q->where('class_id', $request->input('class_id'));
                }
                if ($request->filled('section_id')) {
                    $q->where('section_id', $request->input('section_id'));
                }
                if ($request->filled('subject_id')) {
                    $q->where('subject_id', $request->input('subject_id'));
                }
                if ($request->boolean('is_class_teacher')) {
                    $q->where('is_class_teacher', true);
                }
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('teacher_code', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->input('per_page', 15);
            $users = $query->paginate($perPage);
        } else {
            $users = $query->get();
        }

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $currentUser = $request->user();
        
        // Prevent non-Super Admin from assigning Super Admin role or other schools' roles
        if (isset($request->validated()['role'])) {
            $role = Role::find($request->validated()['role']);
            if ($role) {
                if ($role->name === 'Super Admin' && !$currentUser->isSuperAdmin()) {
                    return response()->json(['message' => 'Unauthorized to assign Super Admin role.'], 403);
                }
                if (!$currentUser->isSuperAdmin() && $role->school_id !== $currentUser->school_id) {
                    return response()->json(['message' => 'Unauthorized to assign this role.'], 403);
                }
            }
        }

        // Determine the school_id for the user
        $schoolId = $currentUser->isSuperAdmin() 
            ? $request->input('school_id') 
            : $currentUser->school_id;

        $user = $this->userService->createUser($request->validated(), $schoolId);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load(['school', 'roles', 'documents', 'assignments.class', 'assignments.section', 'assignments.subject'])
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user, Request $request): JsonResponse
    {
        $this->authorize('user.view');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $user->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json([
            'user' => $user->load(['school', 'roles', 'documents', 'assignments.class', 'assignments.section', 'assignments.subject'])
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $user->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Prevent non-Super Admin from assigning Super Admin role or other schools' roles
        if (isset($request->validated()['role'])) {
            $role = Role::find($request->validated()['role']);
            if ($role) {
                if ($role->name === 'Super Admin' && !$currentUser->isSuperAdmin()) {
                    return response()->json(['message' => 'Unauthorized to assign Super Admin role.'], 403);
                }
                if (!$currentUser->isSuperAdmin() && $role->school_id !== $currentUser->school_id) {
                    return response()->json(['message' => 'Unauthorized to assign this role.'], 403);
                }
            }
        }

        $updatedUser = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $updatedUser->load(['school', 'roles', 'documents', 'assignments.class', 'assignments.section', 'assignments.subject'])
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user, Request $request): JsonResponse
    {
        $this->authorize('user.delete');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $user->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        try {
            $this->userService->deleteUser($user);
            return response()->json([
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Upload user photo and save it.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_id' => 'nullable|integer',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? ($request->input('school_id') ?: 1)
            : $currentUser->school_id;

        $file = $request->file('photo');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '_', $originalName);
        $filename = time() . '_' . $cleanName . '.' . $file->getClientOriginalExtension();
        
        $destinationPath = public_path("uploads/users/{$schoolId}");
        
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        
        $file->move($destinationPath, $filename);
        
        $url = url("uploads/users/{$schoolId}/{$filename}");
        
        return response()->json([
            'url' => $url,
            'path' => "uploads/users/{$schoolId}/{$filename}"
        ], 200);
    }

    /**
     * Upload user document.
     */
    public function uploadDocument(Request $request, User $user): JsonResponse
    {
        $this->authorize('user.edit');
        
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpeg,png,jpg,gif,doc,docx,xls,xlsx|max:4096',
            'document_name' => 'required|string|max:255',
        ]);

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $user->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $file = $request->file('document');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '_', $originalName);
        $filename = time() . '_' . $cleanName . '.' . $file->getClientOriginalExtension();
        
        $schoolId = $user->school_id ?: 1;
        $destinationPath = public_path("uploads/users/{$schoolId}/documents");
        
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        
        $file->move($destinationPath, $filename);
        
        $path = "uploads/users/{$schoolId}/documents/{$filename}";

        $document = \App\Models\UserDocument::create([
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'document_name' => $request->input('document_name'),
            'document_path' => $path,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'document' => $document
        ]);
    }

    /**
     * Delete user document.
     */
    public function deleteDocument(User $user, \App\Models\UserDocument $document): JsonResponse
    {
        $this->authorize('user.edit');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $user->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if ($document->user_id !== $user->id) {
            return response()->json(['message' => 'Document not associated with this user.'], 400);
        }

        // Delete file
        $filePath = public_path($document->document_path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully.'
        ]);
    }

    /**
     * Custom dynamic user report builder.
     */
    public function report(Request $request)
    {
        $this->authorize('user.view');
        $currentUser = $request->user();

        $query = User::query()
            ->with(['school', 'roles']);

        // Scope to school
        if (!$currentUser->isSuperAdmin()) {
            $query->where('users.school_id', $currentUser->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('users.school_id', $request->input('school_id'));
        }

        // Apply filters
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->input('role'))
                  ->orWhere('id', $request->input('role'));
            });
        }
        if ($request->filled('status')) {
            $query->where('users.status', $request->input('status'));
        }
        if ($request->filled('department')) {
            $query->where('users.department', $request->input('department'));
        }
        if ($request->filled('designation')) {
            $query->where('users.designation', $request->input('designation'));
        }

        if ($request->filled('class_id') || $request->filled('section_id') || $request->filled('subject_id')) {
            $query->whereIn('users.id', function($q) use ($request) {
                $q->select('teacher_id')
                  ->from('teacher_assignments');
                if ($request->filled('class_id')) {
                    $q->where('class_id', $request->input('class_id'));
                }
                if ($request->filled('section_id')) {
                    $q->where('section_id', $request->input('section_id'));
                }
                if ($request->filled('subject_id')) {
                    $q->where('subject_id', $request->input('subject_id'));
                }
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('users.employee_id', 'like', "%{$search}%")
                  ->orWhere('users.teacher_code', 'like', "%{$search}%")
                  ->orWhere('users.mobile', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('users.name')->get();

        $columns = $request->input('columns');
        if (empty($columns)) {
            $columns = ['employee_id', 'name', 'email', 'mobile', 'designation', 'status'];
        } elseif (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        $columnMap = [
            'employee_id' => 'Employee ID',
            'name' => 'Full Name',
            'email' => 'Email',
            'mobile' => 'Mobile',
            'gender' => 'Gender',
            'dob' => 'Date of Birth',
            'aadhaar_no' => 'Aadhaar No',
            'pan_no' => 'PAN No',
            'address' => 'Address',
            'emergency_contact_name' => 'Emergency Name',
            'emergency_contact_mobile' => 'Emergency Mobile',
            'status' => 'Status',
            'teacher_code' => 'Teacher Code',
            'qualification' => 'Qualification',
            'experience' => 'Experience',
            'joining_date' => 'Joining Date',
            'department' => 'Department',
            'designation' => 'Designation',
            'employment_type' => 'Employment Type'
        ];

        if ($request->input('export') === 'csv') {
            return $this->exportCustomCSV($users, $columns, $columnMap);
        }

        if ($request->input('export') === 'pdf') {
            $school = $currentUser->school ?: \App\Models\School::first();
            return $this->exportCustomPDF($users, $columns, $columnMap, $school);
        }

        return response()->json([
            'users' => $users,
            'columns' => $columns,
            'columnMap' => $columnMap
        ]);
    }

    /**
     * Export dynamic user report to CSV.
     */
    protected function exportCustomCSV($users, $columns, $columnMap): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_custom_report_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($users, $columns, $columnMap) {
            $handle = fopen('php://output', 'w');

            // Header row
            $headerRow = [];
            foreach ($columns as $col) {
                $headerRow[] = $columnMap[$col] ?? ucfirst(str_replace('_', ' ', $col));
            }
            fputcsv($handle, $headerRow);

            foreach ($users as $user) {
                $row = [];
                foreach ($columns as $col) {
                    if ($col === 'dob') {
                        $row[] = $user->dob ? $user->dob->format('Y-m-d') : '';
                    } elseif ($col === 'joining_date') {
                        $row[] = $user->joining_date ? $user->joining_date->format('Y-m-d') : '';
                    } else {
                        $row[] = $user->$col ?? '';
                    }
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export dynamic user report to PDF.
     */
    protected function exportCustomPDF($users, $columns, $columnMap, $school)
    {
        $html = view('reports.users_custom', compact('users', 'columns', 'columnMap', 'school'))->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        if (count($columns) > 6) {
            $pdf->setPaper('a4', 'landscape');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }
        return $pdf->download('user_custom_report_' . date('Ymd_His') . '.pdf');
    }
}
