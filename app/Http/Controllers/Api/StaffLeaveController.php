<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffLeave;
use App\Models\StaffAttendance;
use App\Models\AcademicYear;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffLeaveController extends Controller
{
    /**
     * Display a listing of leaves.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $currentUser = $request->user();
        $query = StaffLeave::with('user');

        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $leaves = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($leaves);
    }

    /**
     * Approve the leave request.
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $this->authorize('attendance.edit');

        $currentUser = $request->user();
        $leave = StaffLeave::findOrFail($id);

        if (!$currentUser->isSuperAdmin() && $leave->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $leave->status = 'Approved';
        $leave->save();

        // Find Academic Year ID context
        $currentYear = AcademicYear::where('school_id', $leave->school_id)
            ->where('is_current', true)
            ->first();
        $academicYearId = $currentYear ? $currentYear->id : null;
        if (!$academicYearId) {
            $anyYear = AcademicYear::where('school_id', $leave->school_id)->first();
            $academicYearId = $anyYear ? $anyYear->id : null;
        }

        // Auto mark staff attendance as 'Leave' for the specified dates
        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            StaffAttendance::updateOrCreate(
                [
                    'school_id' => $leave->school_id,
                    'user_id' => $leave->user_id,
                    'attendance_date' => $dateString,
                ],
                [
                    'academic_year_id' => $academicYearId,
                    'status' => 'Leave',
                    'remarks' => 'Approved Leave: ' . $leave->leave_type,
                    'marked_by' => $currentUser->id,
                    'is_delete' => 0
                ]
            );
        }

        // Send Push Notification
        try {
            $fcmService = app(FcmService::class);
            $fcmService->sendToUser(
                $leave->user_id,
                "Leave Approved",
                "Your leave request for {$leave->leave_type} from {$leave->start_date} to {$leave->end_date} has been approved.",
                [
                    'type' => 'leave',
                    'status' => 'Approved',
                    'id' => $leave->id
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Leave Approval Notification Error: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave request approved and staff attendance set to Leave.',
            'leave' => $leave
        ]);
    }

    /**
     * Reject the leave request.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $this->authorize('attendance.edit');

        $currentUser = $request->user();
        $leave = StaffLeave::findOrFail($id);

        if (!$currentUser->isSuperAdmin() && $leave->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $leave->status = 'Rejected';
        $leave->save();

        // Remove 'Leave' attendance records for these dates
        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            StaffAttendance::where('school_id', $leave->school_id)
                ->where('user_id', $leave->user_id)
                ->where('attendance_date', $dateString)
                ->where('status', 'Leave')
                ->delete();
        }

        // Send Push Notification
        try {
            $fcmService = app(FcmService::class);
            $fcmService->sendToUser(
                $leave->user_id,
                "Leave Rejected",
                "Your leave request for {$leave->leave_type} from {$leave->start_date} to {$leave->end_date} has been rejected.",
                [
                    'type' => 'leave',
                    'status' => 'Rejected',
                    'id' => $leave->id
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Leave Rejection Notification Error: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave request rejected and attendance entries cleared.',
            'leave' => $leave
        ]);
    }

    /**
     * Delete the leave request.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $this->authorize('attendance.delete');

        $currentUser = $request->user();
        $leave = StaffLeave::findOrFail($id);

        if (!$currentUser->isSuperAdmin() && $leave->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // If it was approved, clean up the attendance records
        if ($leave->status === 'Approved') {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);

            for ($date = $start; $date->lte($end); $date->addDay()) {
                $dateString = $date->format('Y-m-d');
                StaffAttendance::where('school_id', $leave->school_id)
                    ->where('user_id', $leave->user_id)
                    ->where('attendance_date', $dateString)
                    ->where('status', 'Leave')
                    ->delete();
            }
        }

        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave request deleted successfully.'
        ]);
    }
}
