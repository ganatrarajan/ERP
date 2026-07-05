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

            $academicYearId = $request->query('academic_year_id');
            $filterMonth = $request->query('month');
            $classId = $request->query('class_id');
            $sectionId = $request->query('section_id');
            $tab = $request->query('tab'); // V2 dynamic tab workspace segment parameter

            if ($academicYearId) {
                $activeYear = \App\Models\AcademicYear::where('school_id', $schoolId)
                    ->where('id', $academicYearId)
                    ->where('is_delete', 0)
                    ->first();
            } else {
                $activeYear = \App\Models\AcademicYear::where('school_id', $schoolId)
                    ->where('is_current', true)
                    ->where('is_delete', 0)
                    ->first();
            }

            $classPendingFees = [];
            $pendingStudentsCount = 0;
            $classWiseOverview = [];
            $recentActivities = [];

            $totalStudents = 0;
            $totalClasses = 0;
            $totalSubjects = 0;

            // Count teachers specifically (School-wide)
            $teacherQuery = User::where('school_id', $schoolId)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'Teacher');
                });
            if ($activeYear && ($classId || $sectionId)) {
                $teacherQuery->whereIn('id', \App\Models\TeacherAssignment::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->when($classId, fn($q) => $q->where('class_id', $classId))
                    ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                    ->pluck('teacher_id')
                );
            }
            $totalTeachers = $teacherQuery->count();

            if ($activeYear) {
                $studentQuery = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereHas('student', function($q) {
                        $q->where('is_delete', 0);
                    });
                if ($classId) {
                    $studentQuery->where('class_id', $classId);
                }
                if ($sectionId) {
                    $studentQuery->where('section_id', $sectionId);
                }
                $totalStudents = $studentQuery->count();

                $classQuery = \App\Models\ClassModel::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('is_delete', 0);
                if ($classId) {
                    $classQuery->where('id', $classId);
                }
                $totalClasses = $classQuery->count();

                if (schoolHasModule('subjects')) {
                    $subjQuery = \App\Models\Subject::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0);
                    if ($classId) {
                        $subjQuery->where('class_id', $classId);
                    }
                    $totalSubjects = $subjQuery->count();
                }
            }

            // Pre-get all class names for mapping
            $allClasses = $activeYear ? \App\Models\ClassModel::where('school_id', $schoolId)
                ->where('academic_year_id', $activeYear->id)
                ->where('is_delete', 0)
                ->pluck('name', 'id')
                ->toArray() : [];

            $recentCollections = [];
            $monthlyTrends = [];
            $classCollections = [];
            $todayCollection = 0.00;
            $monthlyCollection = 0.00;
            $totalDiscounts = 0.00;
            $totalFines = 0.00;
            $pendingFees = 0.00;
            $overdueFees = 0.00;
            $totalAssignedFees = 0.00;
            $overdueStudentsCount = 0;
            $classDefaulterData = [];

            foreach ($allClasses as $cId => $cName) {
                $classDefaulterData[$cId] = [
                    'class_id' => $cId,
                    'class_name' => $cName,
                    'pending_students' => 0,
                    'pending_amount' => 0.00,
                ];
            }

            $highestMonth = null;
            $lowestMonth = null;
            $growth = 0.00;
            $currentMonthCollection = 0.00;
            $prevMonthCollection = 0.00;

            // Fetch Overview cards fee totals
            if (schoolHasModule('fees') && $user->can('fee_collection.view') && $activeYear) {
                $todayColQuery = \App\Models\FeeCollection::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id);
                if ($filterMonth) {
                    $todayColQuery->whereMonth('payment_date', $filterMonth);
                }
                if ($classId || $sectionId) {
                    $todayColQuery->whereIn('student_id', \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->when($classId, fn($q) => $q->where('class_id', $classId))
                        ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                        ->pluck('student_id')
                    );
                }
                $todayCollection = $todayColQuery->whereDate('payment_date', \Carbon\Carbon::today())->sum('amount_paid');
                
                $monthlyColQuery = \App\Models\FeeCollection::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id);
                if ($filterMonth) {
                    $monthlyColQuery->whereMonth('payment_date', $filterMonth);
                } else {
                    $monthlyColQuery->whereMonth('payment_date', \Carbon\Carbon::today()->month)
                                    ->whereYear('payment_date', \Carbon\Carbon::today()->year);
                }
                if ($classId || $sectionId) {
                    $monthlyColQuery->whereIn('student_id', \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->when($classId, fn($q) => $q->where('class_id', $classId))
                        ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                        ->pluck('student_id')
                    );
                }
                $monthlyCollection = $monthlyColQuery->sum('amount_paid');

                $discQuery = \App\Models\FeeCollection::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id);
                if ($filterMonth) {
                    $discQuery->whereMonth('payment_date', $filterMonth);
                }
                if ($classId || $sectionId) {
                    $discQuery->whereIn('student_id', \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->when($classId, fn($q) => $q->where('class_id', $classId))
                        ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                        ->pluck('student_id')
                    );
                }
                $totalDiscounts = (clone $discQuery)->sum('discount_amount');
                $totalFines = $discQuery->sum('fine_amount');

                // Get assignments
                $assignmentsQuery = \App\Models\StudentFeeAssignment::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id);
                if ($classId || $sectionId) {
                    $assignmentsQuery->whereIn('student_id', \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->when($classId, fn($q) => $q->where('class_id', $classId))
                        ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                        ->pluck('student_id')
                    );
                }
                $assignments = $assignmentsQuery->pluck('student_id')->toArray();

                $studentClasses = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->pluck('class_id', 'student_id')
                    ->toArray();

                $calcService = resolve(\App\Services\FeeCalculationService::class);
                foreach ($assignments as $studentId) {
                    $dues = \Illuminate\Support\Facades\Cache::remember("student_fee_dues_{$studentId}_{$activeYear->id}", 86400, function() use ($calcService, $studentId, $activeYear) {
                        return $calcService->getStudentFeeDues($studentId, $activeYear->id);
                    });

                    if ($dues['has_assignment']) {
                        $totalAssignedFees += $dues['total_fee'];
                        $pendingFees += $dues['outstanding_balance'];
                        if ($dues['outstanding_balance'] > 0) {
                            $pendingStudentsCount++;
                        }
                        
                        $cId = $studentClasses[$studentId] ?? null;
                        if ($cId) {
                            if (!isset($classPendingFees[$cId])) {
                                $classPendingFees[$cId] = 0;
                            }
                            $classPendingFees[$cId] += $dues['outstanding_balance'];

                            if ($dues['outstanding_balance'] > 0 && isset($classDefaulterData[$cId])) {
                                $classDefaulterData[$cId]['pending_students']++;
                                $classDefaulterData[$cId]['pending_amount'] += $dues['outstanding_balance'];
                            }
                        }

                        $hasOverdue = false;
                        foreach ($dues['installments'] as $inst) {
                            if ($inst['is_overdue']) {
                                $overdueFees += $inst['outstanding_balance'];
                                if ($inst['outstanding_balance'] > 0) {
                                    $hasOverdue = true;
                                }
                            }
                        }
                        if ($hasOverdue) {
                            $overdueStudentsCount++;
                        }
                    }
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

            if (schoolHasModule('fees') && $user->can('fee_collection.view')) {
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
                    'title' => 'Total Fee Assigned',
                    'value' => '₹' . number_format($totalAssignedFees, 2),
                    'description' => 'Total fees assigned for this session',
                    'icon' => 'DocumentTextIcon',
                    'color' => 'indigo'
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

            $availableYears = \App\Models\AcademicYear::where('school_id', $schoolId)
                ->where('is_delete', 0)
                ->orderBy('title', 'desc')
                ->get(['id', 'title', 'is_current'])
                ->toArray();

            $availableClasses = $activeYear ? \App\Models\ClassModel::where('school_id', $schoolId)
                ->where('academic_year_id', $activeYear->id)
                ->where('is_delete', 0)
                ->get(['id', 'name'])
                ->toArray() : [];

            $availableSections = [];
            if ($activeYear) {
                $availableSections = \App\Models\Section::where('school_id', $schoolId)
                    ->where('is_delete', 0)
                    ->when($classId, fn($q) => $q->where('class_id', $classId))
                    ->get(['id', 'class_id', 'name'])
                    ->toArray();
            }

            $activeYearData = $activeYear ? [
                'id' => $activeYear->id,
                'title' => $activeYear->title,
                'is_current' => $activeYear->is_current,
            ] : null;

            // ----------------------------------------------------
            // WORKSPACE TAB CALCULATIONS (DYNAMIC / ON-DEMAND LOADING)
            // ----------------------------------------------------
            $tabData = [];
            if ($tab) {
                if ($tab === 'fee') {
                    // Monthly trends (past 6 months)
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

                    $collectionsQuery = \App\Models\FeeCollection::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id);
                    if ($classId || $sectionId) {
                        $collectionsQuery->whereIn('student_id', \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                            ->where('academic_year_id', $activeYear->id)
                            ->when($classId, fn($q) => $q->where('class_id', $classId))
                            ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                            ->pluck('student_id')
                        );
                    }
                    $collections = $collectionsQuery->select('payment_date', 'amount_paid')->get();

                    foreach ($collections as $c) {
                        if ($c->payment_date) {
                            $monthKey = $c->payment_date->format('Y-m');
                            if (isset($monthlyTrendsMap[$monthKey])) {
                                $monthlyTrendsMap[$monthKey]['total'] += (float)$c->amount_paid;
                            }
                        }
                    }
                    ksort($monthlyTrendsMap);
                    $monthlyTrends = array_values($monthlyTrendsMap);

                    if (count($monthlyTrends) > 0) {
                        $tempTrends = $monthlyTrends;
                        usort($tempTrends, fn($a, $b) => $b['total'] <=> $a['total']);
                        $highestMonth = $tempTrends[0];
                        $lowestMonth = $tempTrends[count($tempTrends) - 1];
                    }

                    // Growth calculation
                    $thisMonthKey = \Carbon\Carbon::today()->format('Y-m');
                    $prevMonthKey = \Carbon\Carbon::today()->subMonth()->format('Y-m');
                    $currentMonthCol = $monthlyTrendsMap[$thisMonthKey]['total'] ?? 0.0;
                    $prevMonthCol = $monthlyTrendsMap[$prevMonthKey]['total'] ?? 0.0;
                    $growth = 0.00;
                    if ($prevMonthCol > 0) {
                        $growth = (($currentMonthCol - $prevMonthCol) / $prevMonthCol) * 100;
                    } elseif ($currentMonthCol > 0) {
                        $growth = 100.00;
                    }

                    // Sort defaulters
                    $feeDefaulters = [];
                    foreach ($allClasses as $cId => $cName) {
                        $feeDefaulters[] = [
                            'class_id' => $cId,
                            'class_name' => $cName,
                            'pending_students' => $classDefaulterData[$cId]['pending_students'] ?? 0,
                            'pending_amount' => (float)($classPendingFees[$cId] ?? 0.00),
                        ];
                    }
                    usort($feeDefaulters, fn($a, $b) => $b['pending_amount'] <=> $a['pending_amount']);
                    $feeDefaulters = array_values(array_filter($feeDefaulters, fn($item) => $item['pending_amount'] > 0));

                    $insights = [];
                    if ($growth > 0) {
                        $insights[] = "Fee collection increased by " . number_format($growth, 1) . "% compared to last month.";
                    } elseif ($growth < 0) {
                        $insights[] = "Fee collection decreased by " . number_format(abs($growth), 1) . "% compared to last month.";
                    }
                    if (count($feeDefaulters) > 0) {
                        $insights[] = "Class {$feeDefaulters[0]['class_name']} has the highest pending fees (₹" . number_format($feeDefaulters[0]['pending_amount'], 2) . ").";
                    }
                    $insights[] = "Total outstanding pending dues for this session is ₹" . number_format($pendingFees, 2) . ".";

                    $tabData = [
                        'monthly_trends' => $monthlyTrends,
                        'current_month_collection' => $currentMonthCol,
                        'pending_fee_amount' => $pendingFees,
                        'highest_collection_month' => $highestMonth ? $highestMonth['label'] : 'N/A',
                        'lowest_collection_month' => $lowestMonth ? $lowestMonth['label'] : 'N/A',
                        'top_pending_classes' => array_slice($feeDefaulters, 0, 5),
                        'insights' => $insights,
                    ];
                } elseif ($tab === 'attendance') {
                    $weeklyTrend = [];
                    $recentDates = \App\Models\Attendance::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->select('attendance_date')
                        ->groupBy('attendance_date')
                        ->orderBy('attendance_date', 'desc')
                        ->limit(7)
                        ->pluck('attendance_date')
                        ->toArray();
                    $recentDates = array_reverse($recentDates);

                    foreach ($recentDates as $d) {
                        $pCount = \App\Models\Attendance::where('school_id', $schoolId)
                            ->where('academic_year_id', $activeYear->id)
                            ->where('attendance_date', $d)
                            ->where('status', 'Present')
                            ->where('is_delete', 0)
                            ->count();
                        $tCount = \App\Models\Attendance::where('school_id', $schoolId)
                            ->where('academic_year_id', $activeYear->id)
                            ->where('attendance_date', $d)
                            ->where('is_delete', 0)
                            ->count();
                        $weeklyTrend[] = [
                            'label' => \Carbon\Carbon::parse($d)->format('D d M'),
                            'rate' => $tCount > 0 ? round(($pCount / $tCount) * 100) : 100,
                        ];
                    }

                    $classRates = [];
                    $attByClassMap = [];
                    $classAttRecords = \App\Models\Attendance::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->select('class_id', 'status', \DB::raw('count(*) as count'))
                        ->groupBy('class_id', 'status')
                        ->get();

                    foreach ($classAttRecords as $rec) {
                        if (!isset($attByClassMap[$rec->class_id])) {
                            $attByClassMap[$rec->class_id] = ['Present' => 0, 'Absent' => 0];
                        }
                        $attByClassMap[$rec->class_id][$rec->status] = (int)$rec->count;
                    }

                    foreach ($allClasses as $cId => $cName) {
                        $present = $attByClassMap[$cId]['Present'] ?? 0;
                        $absent = $attByClassMap[$cId]['Absent'] ?? 0;
                        $tot = $present + $absent;
                        $rate = $tot > 0 ? round(($present / $tot) * 100) : 100;
                        
                        $classRates[] = [
                            'class_id' => $cId,
                            'class_name' => $cName,
                            'rate' => $rate,
                            'present' => $present,
                            'total' => $tot,
                        ];
                    }
                    
                    usort($classRates, fn($a, $b) => $b['rate'] <=> $a['rate']);
                    $highestAttClass = count($classRates) > 0 ? $classRates[0] : null;
                    $lowestAttClass = count($classRates) > 0 ? $classRates[count($classRates) - 1] : null;
                    $classesBelowThreshold = array_values(array_filter($classRates, fn($item) => $item['rate'] < 90 && $item['total'] > 0));

                    $todayDate = \Carbon\Carbon::today()->format('Y-m-d');
                    $todayPresent = \App\Models\Attendance::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('attendance_date', $todayDate)
                        ->where('status', 'Present')
                        ->where('is_delete', 0)
                        ->count();
                    $todayTotal = \App\Models\Attendance::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('attendance_date', $todayDate)
                        ->where('is_delete', 0)
                        ->count();
                    $todayAttendancePercent = $todayTotal > 0 ? round(($todayPresent / $todayTotal) * 100) : 100;

                    $yesterdayDate = \Carbon\Carbon::yesterday()->format('Y-m-d');
                    $yesterdayPresent = \App\Models\Attendance::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('attendance_date', $yesterdayDate)
                        ->where('status', 'Present')
                        ->where('is_delete', 0)
                        ->count();
                    $yesterdayTotal = \App\Models\Attendance::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('attendance_date', $yesterdayDate)
                        ->where('is_delete', 0)
                        ->count();
                    $yesterdayRate = $yesterdayTotal > 0 ? round(($yesterdayPresent / $yesterdayTotal) * 100) : null;

                    $insights = [];
                    $insights[] = "Overall student attendance rate for today is {$todayAttendancePercent}%.";
                    if ($yesterdayRate !== null) {
                        if ($todayAttendancePercent > $yesterdayRate) {
                            $insights[] = "Attendance rate improved compared to yesterday ({$todayAttendancePercent}% vs. {$yesterdayRate}%).";
                        } elseif ($todayAttendancePercent < $yesterdayRate) {
                            $insights[] = "Attendance rate dropped compared to yesterday ({$todayAttendancePercent}% vs. {$yesterdayRate}%).";
                        }
                    }
                    if ($lowestAttClass && $lowestAttClass['rate'] < 90) {
                        $insights[] = "Class {$lowestAttClass['class_name']} has the lowest attendance rate ({$lowestAttClass['rate']}%).";
                    }

                    $tabData = [
                        'weekly_trend' => $weeklyTrend,
                        'today_attendance_percent' => $todayAttendancePercent,
                        'lowest_attendance_class' => $lowestAttClass ? "{$lowestAttClass['class_name']} ({$lowestAttClass['rate']}%)" : 'N/A',
                        'highest_attendance_class' => $highestAttClass ? "{$highestAttClass['class_name']} ({$highestAttClass['rate']}%)" : 'N/A',
                        'classes_below_target' => array_slice($classesBelowThreshold, 0, 5),
                        'insights' => $insights,
                    ];
                } elseif ($tab === 'examination') {
                    $totalSchedules = \App\Models\ExamSchedule::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->count();
                    
                    $completedSchedulesCount = 0;
                    $incompleteSchedules = [];
                    $schedulesProgress = [];
                    
                    $schedules = \App\Models\ExamSchedule::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->with(['class', 'subject', 'exam'])
                        ->get();
                    
                    $examStatusMap = [];
                    foreach ($schedules as $sched) {
                        $hasMarks = \App\Models\ExamMark::where('exam_schedule_id', $sched->id)->exists();
                        if ($hasMarks) {
                            $completedSchedulesCount++;
                        } else {
                            $incompleteSchedules[] = [
                                'class_name' => $sched->class ? $sched->class->name : 'Unknown',
                                'subject_name' => $sched->subject ? $sched->subject->name : 'Unknown',
                                'exam_name' => $sched->exam ? $sched->exam->name : 'Unknown',
                                'exam_date' => $sched->exam_date ? $sched->exam_date->format('Y-m-d') : 'N/A',
                            ];
                        }

                        if ($sched->exam) {
                            $examId = $sched->exam->id;
                            if (!isset($examStatusMap[$examId])) {
                                $examStatusMap[$examId] = ['name' => $sched->exam->name, 'total' => 0, 'entered' => 0];
                            }
                            $examStatusMap[$examId]['total']++;
                            if ($hasMarks) {
                                $examStatusMap[$examId]['entered']++;
                            }
                        }
                    }
                    
                    foreach ($examStatusMap as $eId => $eVal) {
                        $schedulesProgress[] = [
                            'exam_name' => $eVal['name'],
                            'rate' => $eVal['total'] > 0 ? round(($eVal['entered'] / $eVal['total']) * 100) : 100,
                        ];
                    }
                    usort($schedulesProgress, fn($a, $b) => $b['rate'] <=> $a['rate']);
                    $schedulesProgress = array_slice($schedulesProgress, 0, 5);

                    $marksProgress = $totalSchedules > 0 ? round(($completedSchedulesCount / $totalSchedules) * 100) : 100;
                    
                    $classAverages = [];
                    $examMarks = \App\Models\ExamMark::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->select('student_id', 'marks_obtained')
                        ->get();
                    
                    if ($examMarks->isNotEmpty()) {
                        $studentIds = $examMarks->pluck('student_id')->unique()->toArray();
                        $studentClasses = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                            ->where('academic_year_id', $activeYear->id)
                            ->whereIn('student_id', $studentIds)
                            ->pluck('class_id', 'student_id')
                            ->toArray();
                        
                        $classMarksSum = [];
                        $classMarksCount = [];
                        foreach ($examMarks as $m) {
                            $cId = $studentClasses[$m->student_id] ?? null;
                            if ($cId) {
                                if (!isset($classMarksSum[$cId])) {
                                    $classMarksSum[$cId] = 0;
                                    $classMarksCount[$cId] = 0;
                                }
                                $classMarksSum[$cId] += (float)$m->marks_obtained;
                                $classMarksCount[$cId]++;
                            }
                        }
                        
                        foreach ($classMarksSum as $cId => $sum) {
                            $count = $classMarksCount[$cId];
                            $classAverages[] = [
                                'class_id' => $cId,
                                'class_name' => $allClasses[$cId] ?? 'Class ' . $cId,
                                'average_score' => $count > 0 ? round($sum / $count, 1) : 0,
                            ];
                        }
                        
                        usort($classAverages, fn($a, $b) => $b['average_score'] <=> $a['average_score']);
                    }
                    
                    $highestPerf = count($classAverages) > 0 ? $classAverages[0] : null;
                    $lowestPerf = count($classAverages) > 0 ? $classAverages[count($classAverages) - 1] : null;

                    $insights = [];
                    $insights[] = "Global marks entry completion rate is {$marksProgress}%.";
                    if (count($incompleteSchedules) > 0) {
                        $insights[] = "Marks entry is pending for " . count($incompleteSchedules) . " subject schedules.";
                    }
                    if ($highestPerf) {
                        $insights[] = "Class {$highestPerf['class_name']} has the highest average performance score ({$highestPerf['average_score']}).";
                    }

                    $tabData = [
                        'marks_entry_progress_percent' => $marksProgress,
                        'pending_marks_entry_count' => count($incompleteSchedules),
                        'result_completion_status' => "{$completedSchedulesCount}/{$totalSchedules} Entered",
                        'highest_performing_class' => $highestPerf ? "{$highestPerf['class_name']} ({$highestPerf['average_score']})" : 'N/A',
                        'lowest_performing_class' => $lowestPerf ? "{$lowestPerf['class_name']} ({$lowestPerf['average_score']})" : 'N/A',
                        'top_performing_classes' => array_slice($classAverages, 0, 5),
                        'schedules_progress' => $schedulesProgress,
                        'insights' => $insights,
                    ];
                } elseif ($tab === 'student') {
                    $totalStrength = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->whereHas('student', fn($q) => $q->where('is_delete', 0))
                        ->count();

                    $maleCount = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->whereHas('student', fn($q) => $q->where('is_delete', 0)->where('gender', 'Male'))
                        ->count();

                    $femaleCount = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->whereHas('student', fn($q) => $q->where('is_delete', 0)->where('gender', 'Female'))
                        ->count();

                    $boysPercent = $totalStrength > 0 ? round(($maleCount / $totalStrength) * 100) : 50;
                    $girlsPercent = $totalStrength > 0 ? round(($femaleCount / $totalStrength) * 100) : 50;

                    $newAdmissionsCount = \App\Models\Student::where('school_id', $schoolId)
                        ->where('is_delete', 0)
                        ->whereBetween('admission_date', [
                            $activeYear->start_date ?? \Carbon\Carbon::today()->startOfYear()->format('Y-m-d'),
                            $activeYear->end_date ?? \Carbon\Carbon::today()->endOfYear()->format('Y-m-d')
                        ])
                        ->count();

                    $prevYear = \App\Models\AcademicYear::where('school_id', $schoolId)
                        ->where('id', '<', $activeYear->id)
                        ->where('is_delete', 0)
                        ->orderBy('id', 'desc')
                        ->first();
                    $prevStrength = 0;
                    if ($prevYear) {
                        $prevStrength = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                            ->where('academic_year_id', $prevYear->id)
                            ->whereHas('student', fn($q) => $q->where('is_delete', 0))
                            ->count();
                    }
                    $growthRate = $prevStrength > 0 ? round((($totalStrength - $prevStrength) / $prevStrength) * 100, 1) : 100;

                    $admissionsTrend = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $date = \Carbon\Carbon::today()->subMonths($i);
                        $mCount = \App\Models\Student::where('school_id', $schoolId)
                            ->where('is_delete', 0)
                            ->whereBetween('admission_date', [
                                $date->copy()->startOfMonth()->format('Y-m-d'),
                                $date->copy()->endOfMonth()->format('Y-m-d')
                            ])
                            ->count();
                        
                        $admissionsTrend[] = [
                            'label' => $date->format('M Y'),
                            'count' => $mCount,
                        ];
                    }

                    $classSizes = [];
                    $classSizesRecords = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->select('class_id', \DB::raw('count(*) as count'))
                        ->groupBy('class_id')
                        ->get();
                    foreach ($classSizesRecords as $r) {
                        $classSizes[] = [
                            'class_id' => $r->class_id,
                            'class_name' => $allClasses[$r->class_id] ?? 'Class ' . $r->class_id,
                            'student_count' => $r->count,
                        ];
                    }
                    usort($classSizes, fn($a, $b) => $b['student_count'] <=> $a['student_count']);

                    $insights = [];
                    $insights[] = "Total active enrollment is currently {$totalStrength} students.";
                    $insights[] = "Boys/Girls distribution stands at {$boysPercent}% Boys and {$girlsPercent}% Girls.";
                    if ($growthRate > 0) {
                        $insights[] = "Student enrollment increased by {$growthRate}% compared to previous session.";
                    }
                    $insights[] = "{$newAdmissionsCount} new admissions registered during this academic session.";

                    $tabData = [
                        'new_admissions_count' => $newAdmissionsCount,
                        'total_strength' => $totalStrength,
                        'ratio' => "{$boysPercent}% / {$girlsPercent}%",
                        'growth_rate' => $growthRate,
                        'admissions_trend' => $admissionsTrend,
                        'class_distribution' => array_slice($classSizes, 0, 5),
                        'insights' => $insights,
                    ];
                } elseif ($tab === 'teacher') {
                    $teachersQuery = User::where('school_id', $schoolId)
                        ->whereHas('roles', fn($q) => $q->where('name', 'Teacher'));
                    $totalTeachersCount = $teachersQuery->count();

                    $todayDate = \Carbon\Carbon::today()->format('Y-m-d');
                    $teacherPresent = \App\Models\StaffAttendance::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('attendance_date', $todayDate)
                        ->where('status', 'Present')
                        ->where('is_delete', 0)
                        ->count();

                    $teacherAbsent = \App\Models\StaffAttendance::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('attendance_date', $todayDate)
                        ->where('status', 'Absent')
                        ->where('is_delete', 0)
                        ->count();

                    $teacherLeaves = \App\Models\StaffLeave::where('school_id', $schoolId)
                        ->where('status', 'approved')
                        ->whereDate('leave_from_date', '<=', $todayDate)
                        ->whereDate('leave_to_date', '>=', $todayDate)
                        ->count();

                    $unmarkedTeachers = max(0, $totalTeachersCount - ($teacherPresent + $teacherAbsent));
                    $teacherAttRate = $totalTeachersCount > 0 ? round(($teacherPresent / $totalTeachersCount) * 100) : 100;

                    $hwAssignedToday = \App\Models\Homework::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->whereDate('created_at', $todayDate)
                        ->count();

                    $pendingLeavesCount = \App\Models\StaffLeave::where('school_id', $schoolId)
                        ->where('status', 'pending')
                        ->count();

                    $totalAssignments = \App\Models\TeacherAssignment::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->count();
                    $avgAssignedClasses = $totalTeachersCount > 0 ? round($totalAssignments / $totalTeachersCount, 1) : 0;

                    $thisWeekStart = \Carbon\Carbon::today()->startOfWeek()->format('Y-m-d');
                    $thisWeekEnd = \Carbon\Carbon::today()->endOfWeek()->format('Y-m-d');
                    $prevWeekStart = \Carbon\Carbon::today()->subWeek()->startOfWeek()->format('Y-m-d');
                    $prevWeekEnd = \Carbon\Carbon::today()->subWeek()->endOfWeek()->format('Y-m-d');

                    $hwThisWeek = \App\Models\Homework::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])
                        ->count();
                    $hwPrevWeek = \App\Models\Homework::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->whereBetween('created_at', [$prevWeekStart, $prevWeekEnd])
                        ->count();

                    $pendingHomeworksList = \App\Models\Homework::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->where('status', 'active')
                        ->whereDate('submission_date', '<', $todayDate)
                        ->with(['class', 'subject'])
                        ->orderBy('submission_date', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(fn($hw) => [
                            'class_name' => $hw->class ? $hw->class->name : 'Unknown',
                            'subject_name' => $hw->subject ? $hw->subject->name : 'Unknown',
                            'title' => $hw->title,
                            'submission_date' => $hw->submission_date instanceof \Carbon\Carbon ? $hw->submission_date->format('Y-m-d') : ($hw->submission_date ? \Carbon\Carbon::parse($hw->submission_date)->format('Y-m-d') : 'N/A'),
                        ])
                        ->toArray();

                    $insights = [];
                    $insights[] = "Teacher attendance rate for today is {$teacherAttRate}%.";
                    if ($pendingLeavesCount > 0) {
                        $insights[] = "There are {$pendingLeavesCount} staff leave requests awaiting approval.";
                    }
                    $insights[] = "{$hwAssignedToday} new homework tasks were created/assigned today.";
                    if ($unmarkedTeachers > 0) {
                        $insights[] = "{$unmarkedTeachers} teachers have not submitted attendance today.";
                    }

                    $tabData = [
                        'teacher_attendance_rate' => $teacherAttRate,
                        'homework_assigned_today' => $hwAssignedToday,
                        'pending_leaves_count' => $pendingLeavesCount,
                        'avg_assigned_classes' => $avgAssignedClasses,
                        'attendance_chart_data' => [
                            ['label' => 'Present', 'count' => $teacherPresent],
                            ['label' => 'Absent', 'count' => $teacherAbsent],
                            ['label' => 'Leave', 'count' => $teacherLeaves],
                            ['label' => 'Unmarked', 'count' => $unmarkedTeachers],
                        ],
                        'weekly_homework_trend' => [
                            ['label' => 'Prev Week', 'count' => $hwPrevWeek],
                            ['label' => 'This Week', 'count' => $hwThisWeek],
                        ],
                        'pending_homeworks' => $pendingHomeworksList,
                        'insights' => $insights,
                    ];
                }

                return response()->json([
                    'scope' => 'school',
                    'stats' => $stats,
                    'available_years' => $availableYears,
                    'available_classes' => $availableClasses,
                    'available_sections' => $availableSections,
                    'active_academic_year' => $activeYearData,
                    'tab' => $tab,
                    'data' => $tabData,
                ]);
            }

            // ----------------------------------------------------
            // BACKWARD COMPATIBLE FULL PAYLOAD (IF NO TAB PARAM SUPPLIED)
            // ----------------------------------------------------
            $todayActions = [];
            $feeAnalytics = [];
            $attendanceAnalytics = [];
            $classHealth = [];
            $feeDefaulters = [];
            $smartInsights = [];
            $performanceSummary = [];

            if ($activeYear) {
                // Get today's unmarked class sections
                $classes = \App\Models\ClassModel::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('is_delete', 0)
                    ->with('sections')
                    ->get();

                $markedClassSectionIds = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('is_delete', 0)
                    ->select('class_id', 'section_id')
                    ->groupBy('class_id', 'section_id')
                    ->get()
                    ->map(fn($item) => $item->class_id . '-' . $item->section_id)
                    ->toArray();

                $attendancePending = [];
                foreach ($classes as $c) {
                    foreach ($c->sections as $sec) {
                        $key = $c->id . '-' . $sec->id;
                        if (!in_array($key, $markedClassSectionIds)) {
                            $attendancePending[] = [
                                'class_id' => $c->id,
                                'class_name' => $c->name,
                                'section_id' => $sec->id,
                                'section_name' => $sec->name,
                            ];
                        }
                    }
                }

                // Get homework details
                $pendingHomeworks = [];
                if (schoolHasModule('homework')) {
                    $pendingHomeworks = \App\Models\Homework::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->where('is_delete', 0)
                        ->where('status', 'active')
                        ->where('submission_date', '>=', \Carbon\Carbon::today()->format('Y-m-d'))
                        ->with(['class', 'subject'])
                        ->orderBy('submission_date', 'asc')
                        ->limit(5)
                        ->get()
                        ->map(fn($hw) => [
                            'class_name' => $hw->class ? $hw->class->name : 'N/A',
                            'subject_name' => $hw->subject ? $hw->subject->name : 'N/A',
                            'title' => $hw->title,
                            'submission_date' => $hw->submission_date instanceof \Carbon\Carbon ? $hw->submission_date->format('Y-m-d') : ($hw->submission_date ? \Carbon\Carbon::parse($hw->submission_date)->format('Y-m-d') : null),
                        ])->toArray();
                }

                $todayActions = [
                    'attendance_pending' => $attendancePending,
                    'homework_pending' => $pendingHomeworks,
                    'marks_entry_pending' => [],
                    'leave_approvals_pending' => [],
                    'fee_followups_count' => $overdueStudentsCount,
                ];

                // 1. Calculate attendance stats
                $attendanceStats = [
                    'student' => ['present' => 0, 'absent' => 0],
                    'staff' => ['present' => 0, 'absent' => 0],
                ];
                $attendanceStats['student']['present'] = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Present')
                    ->where('is_delete', 0)
                    ->count();
                $attendanceStats['student']['absent'] = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Absent')
                    ->where('is_delete', 0)
                    ->count();

                $attendanceStats['staff']['present'] = \App\Models\StaffAttendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Present')
                    ->where('is_delete', 0)
                    ->count();
                $attendanceStats['staff']['absent'] = \App\Models\StaffAttendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Absent')
                    ->where('is_delete', 0)
                    ->count();

                // 2. Class collections
                if (schoolHasModule('fees') && $user->can('fee_collection.view')) {
                    $recentCollections = \App\Models\FeeCollection::with(['student'])
                        ->where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(fn($f) => [
                            'student_name' => $f->student ? ($f->student->first_name . ' ' . $f->student->last_name) : 'Unknown',
                            'amount' => $f->amount_paid,
                            'date' => $f->payment_date ? $f->payment_date->format('Y-m-d') : null,
                        ])->toArray();

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
                    foreach ($collections as $c) {
                        if ($c->payment_date) {
                            $monthKey = $c->payment_date->format('Y-m');
                            if (isset($monthlyTrendsMap[$monthKey])) {
                                $monthlyTrendsMap[$monthKey]['total'] += (float)$c->amount_paid;
                            }
                        }
                    }
                    ksort($monthlyTrendsMap);
                    $monthlyTrends = array_values($monthlyTrendsMap);

                    $classColMap = [];
                    foreach ($allClasses as $cId => $cName) {
                        $classColMap[$cId] = ['class_name' => $cName, 'total' => 0.0];
                    }
                    $studentClassAssignments = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->pluck('class_id', 'student_id')
                        ->toArray();
                    $collectionsWithStudent = \App\Models\FeeCollection::where('school_id', $schoolId)
                        ->where('academic_year_id', $activeYear->id)
                        ->select('student_id', 'amount_paid')
                        ->get();
                    foreach ($collectionsWithStudent as $c) {
                        $cId = $studentClassAssignments[$c->student_id] ?? null;
                        if ($cId && isset($classColMap[$cId])) {
                            $classColMap[$cId]['total'] += (float)$c->amount_paid;
                        }
                    }
                    usort($classColMap, fn($a, $b) => $b['total'] <=> $a['total']);
                    $classCollections = array_slice(array_values($classColMap), 0, 5);
                }

                // 3. Class-wise attendance overview
                $classesList = \App\Models\ClassModel::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('is_delete', 0)
                    ->with('sections')
                    ->get();
                $studentCounts = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->select('class_id', \DB::raw('count(*) as count'))
                    ->groupBy('class_id')
                    ->pluck('count', 'class_id')
                    ->toArray();
                $presentCounts = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Present')
                    ->where('is_delete', 0)
                    ->select('class_id', \DB::raw('count(*) as count'))
                    ->groupBy('class_id')
                    ->pluck('count', 'class_id')
                    ->toArray();
                $absentCounts = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Absent')
                    ->where('is_delete', 0)
                    ->select('class_id', \DB::raw('count(*) as count'))
                    ->groupBy('class_id')
                    ->pluck('count', 'class_id')
                    ->toArray();
                $studentSectionCounts = \App\Models\StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->select('class_id', 'section_id', \DB::raw('count(*) as count'))
                    ->groupBy('class_id', 'section_id')
                    ->get()
                    ->groupBy('class_id')
                    ->map(fn($items) => $items->pluck('count', 'section_id')->toArray())
                    ->toArray();
                $presentSectionCounts = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Present')
                    ->where('is_delete', 0)
                    ->select('class_id', 'section_id', \DB::raw('count(*) as count'))
                    ->groupBy('class_id', 'section_id')
                    ->get()
                    ->groupBy('class_id')
                    ->map(fn($items) => $items->pluck('count', 'section_id')->toArray())
                    ->toArray();
                $absentSectionCounts = \App\Models\Attendance::where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereDate('attendance_date', \Carbon\Carbon::today()->format('Y-m-d'))
                    ->where('status', 'Absent')
                    ->where('is_delete', 0)
                    ->select('class_id', 'section_id', \DB::raw('count(*) as count'))
                    ->groupBy('class_id', 'section_id')
                    ->get()
                    ->groupBy('class_id')
                    ->map(fn($items) => $items->pluck('count', 'section_id')->toArray())
                    ->toArray();

                foreach ($classesList as $c) {
                    $classSections = [];
                    foreach ($c->sections as $sec) {
                        $classSections[] = [
                            'section_id' => $sec->id,
                            'section_name' => $sec->name,
                            'students' => $studentSectionCounts[$c->id][$sec->id] ?? 0,
                            'present' => $presentSectionCounts[$c->id][$sec->id] ?? 0,
                            'absent' => $absentSectionCounts[$c->id][$sec->id] ?? 0,
                        ];
                    }
                    $classWiseOverview[] = [
                        'class_id' => $c->id,
                        'class_name' => $c->name,
                        'students' => $studentCounts[$c->id] ?? 0,
                        'present' => $presentCounts[$c->id] ?? 0,
                        'absent' => $absentCounts[$c->id] ?? 0,
                        'pending_fee' => (float)($classPendingFees[$c->id] ?? 0.00),
                        'sections' => $classSections,
                    ];
                }

                // 4. Recent Activities
                $activities = [];
                $recentStudents = \App\Models\Student::where('school_id', $schoolId)
                    ->where('is_delete', 0)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                foreach ($recentStudents as $s) {
                    $activities[] = [
                        'type' => 'student_added',
                        'title' => 'New Student Added',
                        'description' => "Student {$s->first_name} {$s->last_name} (Admn No: {$s->admission_no}) was registered.",
                        'user_name' => 'Admin',
                        'timestamp' => $s->created_at ? $s->created_at->toIso8601String() : null,
                        'created_at' => $s->created_at ? $s->created_at->toIso8601String() : null,
                    ];
                }
                if (schoolHasModule('fees') && $user->can('fee_collection.view')) {
                    $recentFees = \App\Models\FeeCollection::with(['student', 'collectedBy'])
                        ->where('school_id', $schoolId)
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();
                    foreach ($recentFees as $f) {
                        $studentName = $f->student ? ($f->student->first_name . ' ' . $f->student->last_name) : 'Unknown';
                        $collectedByName = $f->collectedBy ? $f->collectedBy->name : 'Staff';
                        $activities[] = [
                            'type' => 'fee_collected',
                            'title' => 'Fee Collected',
                            'description' => "Collected fee of ₹" . number_format($f->amount_paid, 2) . " for student {$studentName}.",
                            'user_name' => $collectedByName,
                            'timestamp' => $f->created_at ? $f->created_at->toIso8601String() : null,
                            'created_at' => $f->created_at ? $f->created_at->toIso8601String() : null,
                        ];
                    }
                }
                if (schoolHasModule('attendance')) {
                    $recentAttendance = \App\Models\Attendance::with(['class', 'section', 'markedByUser'])
                        ->where('school_id', $schoolId)
                        ->where('is_delete', 0)
                        ->select('class_id', 'section_id', 'marked_by', 'attendance_date', \DB::raw('MAX(created_at) as created_at'))
                        ->groupBy('class_id', 'section_id', 'marked_by', 'attendance_date')
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();
                    foreach ($recentAttendance as $att) {
                        $className = $att->class ? $att->class->name : 'N/A';
                        $sectionName = $att->section ? $att->section->name : 'N/A';
                        $markedByName = $att->markedByUser ? $att->markedByUser->name : 'Teacher';
                        $activities[] = [
                            'type' => 'attendance_submitted',
                            'title' => 'Attendance Submitted',
                            'description' => "Attendance submitted for Class {$className} - {$sectionName} on " . $att->attendance_date,
                            'user_name' => $markedByName,
                            'timestamp' => $att->created_at ? \Carbon\Carbon::parse($att->created_at)->toIso8601String() : null,
                            'created_at' => $att->created_at ? \Carbon\Carbon::parse($att->created_at)->toIso8601String() : null,
                        ];
                    }
                }
                $recentTeachers = \App\Models\User::where('school_id', $schoolId)
                    ->whereHas('roles', fn($q) => $q->where('name', 'Teacher'))
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                foreach ($recentTeachers as $t) {
                    $activities[] = [
                        'type' => 'teacher_added',
                        'title' => 'Teacher Added',
                        'description' => "Teacher account created for {$t->name} ({$t->email}).",
                        'user_name' => 'Admin',
                        'timestamp' => $t->created_at ? $t->created_at->toIso8601String() : null,
                        'created_at' => $t->created_at ? $t->created_at->toIso8601String() : null,
                    ];
                }
                if (schoolHasModule('notices')) {
                    $recentNotices = \App\Models\Notice::with(['creator'])
                        ->where('school_id', $schoolId)
                        ->where('is_delete', 0)
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();
                    foreach ($recentNotices as $n) {
                        $creatorName = $n->creator ? $n->creator->name : 'Admin';
                        $activities[] = [
                            'type' => 'notice_published',
                            'title' => 'Notice Published',
                            'description' => "Published notice: \"{$n->title}\"",
                            'user_name' => $creatorName,
                            'timestamp' => $n->created_at ? $n->created_at->toIso8601String() : null,
                            'created_at' => $n->created_at ? $n->created_at->toIso8601String() : null,
                        ];
                    }
                }
                usort($activities, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
                $recentActivities = array_slice($activities, 0, 10);
            }

            return response()->json([
                'scope' => 'school',
                'stats' => $stats,
                'available_years' => $availableYears,
                'available_classes' => $availableClasses,
                'available_sections' => $availableSections,
                'active_academic_year' => $activeYearData,
                'today_actions' => $todayActions,
                'fee_analytics' => $feeAnalytics,
                'attendance_analytics' => $attendanceAnalytics,
                'class_health' => $classHealth,
                'fee_defaulters' => $feeDefaulters,
                'smart_insights' => $smartInsights,
                'performance_summary' => $performanceSummary,
                'attendance_stats' => $attendanceStats,
                'recent_collections' => $recentCollections,
                'monthly_trends' => $monthlyTrends,
                'class_collections' => $classCollections,
                'pending_students_count' => $pendingStudentsCount,
                'class_wise_overview' => $classWiseOverview,
                'recent_activities' => $recentActivities,
            ]);
        }
    }
}
