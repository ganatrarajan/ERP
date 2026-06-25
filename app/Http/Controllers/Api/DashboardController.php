<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('dashboard.view');

        $user = $request->user();

        if ($user->isSuperAdmin()) {
            // Super Admin stats
            $totalSchools = School::count();
            $activeSchools = School::where('status', 'active')->count();
            $inactiveSchools = School::where('status', 'inactive')->count();
            $totalUsers = User::count();

            return response()->json([
                'scope' => 'super_admin',
                'stats' => [
                    [
                        'title' => 'Total Schools',
                        'value' => $totalSchools,
                        'description' => 'All registered institutions',
                        'icon' => 'AcademicCapIcon',
                        'color' => 'indigo'
                    ],
                    [
                        'title' => 'Active Schools',
                        'value' => $activeSchools,
                        'description' => 'Schools marked active',
                        'icon' => 'CheckCircleIcon',
                        'color' => 'emerald'
                    ],
                    [
                        'title' => 'Inactive Schools',
                        'value' => $inactiveSchools,
                        'description' => 'Schools marked inactive',
                        'icon' => 'XCircleIcon',
                        'color' => 'rose'
                    ],
                    [
                        'title' => 'Total Users',
                        'value' => $totalUsers,
                        'description' => 'Total platform user accounts',
                        'icon' => 'UsersIcon',
                        'color' => 'blue'
                    ],
                ]
            ]);
        } else {
            // School Admin / Teacher stats (scoped to their own school)
            $schoolId = $user->school_id;

            $activeYear = \App\Models\AcademicYear::where('school_id', $schoolId)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->first();

            $totalStudents = 0;
            $totalClasses = 0;
            $totalSubjects = 0;

            // Count teachers specifically (School-wide, not tied to academic year)
            $totalTeachers = User::where('school_id', $schoolId)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'Teacher');
                })
                ->count();

            if ($activeYear) {
                $totalStudents = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereHas('student', function($q) {
                        $q->where('is_delete', 0);
                    })
                    ->count();

                $totalClasses = \App\Models\ClassModel::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('is_delete', 0)
                    ->count();

                if (schoolHasModule('subjects')) {
                    $totalSubjects = \App\Models\Subject::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->count();
                }
            }

            $stats = [
                [
                    'title' => 'Total Students',
                    'value' => $totalStudents,
                    'description' => 'Active student registrations',
                    'icon' => 'UsersIcon',
                    'color' => 'indigo'
                ],
                [
                    'title' => 'Total Teachers',
                    'value' => $totalTeachers,
                    'description' => 'Active teacher accounts',
                    'icon' => 'BookOpenIcon',
                    'color' => 'sky'
                ],
                [
                    'title' => 'Total Classes',
                    'value' => $totalClasses,
                    'description' => 'Classes defined in current session',
                    'icon' => 'AcademicCapIcon',
                    'color' => 'emerald'
                ]
            ];

            if (schoolHasModule('subjects')) {
                $stats[] = [
                    'title' => 'Total Subjects',
                    'value' => $totalSubjects,
                    'description' => 'Academic course subjects',
                    'icon' => 'BookOpenIcon',
                    'color' => 'violet'
                ];
            }

            $recentCollections = [];
            $monthlyTrends = [];
            $classCollections = [];

            if (schoolHasModule('fees') && $user->can('fee_collection.view')) {
                $todayCollection = 0.00;
                $monthlyCollection = 0.00;
                $totalDiscounts = 0.00;
                $totalFines = 0.00;
                $pendingFees = 0.00;
                $overdueFees = 0.00;

                if ($activeYear) {
                    $todayCollection = \App\Models\FeeCollection::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->whereDate('payment_date', \Carbon\Carbon::today())
                        ->sum('amount_paid');
                    
                    $monthlyCollection = \App\Models\FeeCollection::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->whereMonth('payment_date', \Carbon\Carbon::today()->month)
                        ->whereYear('payment_date', \Carbon\Carbon::today()->year)
                        ->sum('amount_paid');

                    $totalDiscounts = \App\Models\FeeCollection::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->sum('discount_amount');

                    $totalFines = \App\Models\FeeCollection::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->sum('fine_amount');
                    $assignments = \App\Models\StudentFeeAssignment::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->pluck('student_id')
                        ->toArray();

                    $calcService = resolve(\App\Services\FeeCalculationService::class);
                    foreach ($assignments as $studentId) {
                        $dues = $calcService->getStudentFeeDues($studentId, $activeYear->id);
                        if ($dues['has_assignment']) {
                            $pendingFees += $dues['outstanding_balance'];
                            foreach ($dues['installments'] as $inst) {
                                if ($inst['is_overdue']) {
                                    $overdueFees += $inst['outstanding_balance'];
                                }
                            }
                        }
                    }

                    // Get recent collections
                    $recentCollections = \App\Models\FeeCollection::with(['student', 'installment'])
                        ->where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->orderBy('payment_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(function ($collection) {
                            return [
                                'id' => $collection->id,
                                'student_name' => $collection->student ? ($collection->student->first_name . ' ' . $collection->student->last_name) : 'Unknown',
                                'admission_no' => $collection->student ? $collection->student->admission_no : 'N/A',
                                'installment_name' => $collection->installment ? $collection->installment->installment_name : 'N/A',
                                'payment_date' => $collection->payment_date ? $collection->payment_date->format('Y-m-d') : null,
                                'payment_method' => $collection->payment_method,
                                'amount_paid' => (float) $collection->amount_paid,
                            ];
                        })->toArray();

                    // Pre-fill last 6 months (including current month) with 0
                    $monthlyTrendsMap = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $date = \Carbon\Carbon::today()->subMonths($i);
                        $monthKey = $date->format('Y-m');
                        $monthlyTrendsMap[$monthKey] = [
                            'month' => $monthKey,
                            'label' => $date->format('M Y'),
                            'total' => 0.0,
                        ];
                    }

                    $collections = \App\Models\FeeCollection::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->select('payment_date', 'amount_paid')
                        ->get();

                    if ($collections->isNotEmpty()) {
                        foreach ($collections as $c) {
                            if ($c->payment_date) {
                                $monthKey = $c->payment_date->format('Y-m');
                                if (isset($monthlyTrendsMap[$monthKey])) {
                                    $monthlyTrendsMap[$monthKey]['total'] += $c->amount_paid;
                                } else {
                                    $monthlyTrendsMap[$monthKey] = [
                                        'month' => $monthKey,
                                        'label' => $c->payment_date->format('M Y'),
                                        'total' => (float)$c->amount_paid,
                                    ];
                                }
                            }
                        }
                    }
                    
                    ksort($monthlyTrendsMap);
                    $monthlyTrends = array_values($monthlyTrendsMap);

                    // Get class collections
                    $classData = \DB::table('fee_collections')
                        ->join('student_academic_records', function ($join) {
                            $join->on('fee_collections.student_id', '=', 'student_academic_records.student_id')
                                 ->on('fee_collections.academic_year_id', '=', 'student_academic_records.academic_year_id');
                        })
                        ->join('classes', 'student_academic_records.class_id', '=', 'classes.id')
                        ->where('fee_collections.school_id', $schoolId)
                        ->where('fee_collections.academic_year_id', $activeYear->id)
                        ->select('classes.name as class_name', \DB::raw('SUM(fee_collections.amount_paid) as total'))
                        ->groupBy('classes.id', 'classes.name')
                        ->orderBy('total', 'desc')
                        ->get();

                    $classCollections = $classData->map(function ($item) {
                        return [
                            'class_name' => $item->class_name,
                            'total' => (float) $item->total,
                        ];
                    })->toArray();
                }

                $stats[] = [
                    'title' => "Today's Collection",
                    'value' => '₹' . number_format($todayCollection, 2),
                    'description' => 'Fees collected today',
                    'icon' => 'CheckCircleIcon',
                    'color' => 'teal'
                ];

                $stats[] = [
                    'title' => 'Monthly Collection',
                    'value' => '₹' . number_format($monthlyCollection, 2),
                    'description' => 'Fees collected this month',
                    'icon' => 'CheckCircleIcon',
                    'color' => 'emerald'
                ];

                $stats[] = [
                    'title' => 'Pending Fees',
                    'value' => '₹' . number_format($pendingFees, 2),
                    'description' => 'Total outstanding fees due',
                    'icon' => 'DocumentTextIcon',
                    'color' => 'amber'
                ];

                $stats[] = [
                    'title' => 'Overdue Fees',
                    'value' => '₹' . number_format($overdueFees, 2),
                    'description' => 'Dues past their installment deadlines',
                    'icon' => 'BellIcon',
                    'color' => 'rose'
                ];

                $stats[] = [
                    'title' => 'Total Discounts',
                    'value' => '₹' . number_format($totalDiscounts, 2),
                    'description' => 'Total fee discounts applied',
                    'icon' => 'CheckCircleIcon',
                    'color' => 'indigo'
                ];

                $stats[] = [
                    'title' => 'Total Fines',
                    'value' => '₹' . number_format($totalFines, 2),
                    'description' => 'Total overdue fine charges collected',
                    'icon' => 'BellIcon',
                    'color' => 'pink'
                ];
            }

            $attendanceStats = null;
            if (schoolHasModule('attendance') && $activeYear) {
                // Student attendance stats for today
                $studentPresent = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Present')
                    ->where('is_delete', 0)
                    ->count();
                $studentAbsent = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Absent')
                    ->where('is_delete', 0)
                    ->count();

                // Staff attendance stats for today
                $staffPresent = \App\Models\StaffAttendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Present')
                    ->where('is_delete', 0)
                    ->count();
                $staffAbsent = \App\Models\StaffAttendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Absent')
                    ->where('is_delete', 0)
                    ->count();

                $attendanceStats = [
                    'student' => [
                        'present' => $studentPresent,
                        'absent' => $studentAbsent,
                    ],
                    'staff' => [
                        'present' => $staffPresent,
                        'absent' => $staffAbsent,
                    ]
                ];
            }

            return response()->json([
                'scope' => 'school',
                'stats' => $stats,
                'attendance_stats' => $attendanceStats,
                'recent_collections' => $recentCollections,
                'monthly_trends' => $monthlyTrends,
                'class_collections' => $classCollections,
            ]);
        }
    }
}
