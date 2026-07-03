import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/Login.vue'),
        meta: { guestOnly: true, layout: 'auth' }
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('../pages/ForgotPassword.vue'),
        meta: { guestOnly: true, layout: 'auth' }
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('../pages/ResetPassword.vue'),
        meta: { guestOnly: true, layout: 'auth' }
    },
    {
        path: '/',
        redirect: '/dashboard'
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('../pages/Dashboard.vue'),
        meta: { requiresAuth: true, permission: 'dashboard.view', layout: 'app' }
    },
    {
        path: '/schools',
        name: 'schools.index',
        component: () => import('../pages/schools/Index.vue'),
        meta: { requiresAuth: true, permission: 'school.view', layout: 'app' }
    },
    {
        path: '/schools/create',
        name: 'schools.create',
        component: () => import('../pages/schools/Form.vue'),
        meta: { requiresAuth: true, permission: 'school.create', layout: 'app' }
    },
    {
        path: '/schools/:id/edit',
        name: 'schools.edit',
        component: () => import('../pages/schools/Form.vue'),
        meta: { requiresAuth: true, layout: 'app' } // Custom authorization check in form
    },
    {
        path: '/users',
        name: 'users.index',
        component: () => import('../pages/users/Index.vue'),
        meta: { requiresAuth: true, permission: 'user.view', layout: 'app' }
    },
    {
        path: '/users/create',
        name: 'users.create',
        component: () => import('../pages/users/Form.vue'),
        meta: { requiresAuth: true, permission: 'user.create', layout: 'app' }
    },
    {
        path: '/users/reports',
        name: 'users.reports',
        component: () => import('../pages/users/Reports.vue'),
        meta: { requiresAuth: true, permission: 'user.view', layout: 'app' }
    },
    {
        path: '/users/:id/edit',
        name: 'users.edit',
        component: () => import('../pages/users/Form.vue'),
        meta: { requiresAuth: true, permission: 'user.edit', layout: 'app' }
    },
    {
        path: '/users/:id',
        name: 'users.show',
        component: () => import('../pages/users/Show.vue'),
        meta: { requiresAuth: true, permission: 'user.view', layout: 'app' }
    },
    {
        path: '/roles',
        name: 'roles.index',
        component: () => import('../pages/roles/Index.vue'),
        meta: { requiresAuth: true, permission: 'role.view', layout: 'app' }
    },
    {
        path: '/roles/create',
        name: 'roles.create',
        component: () => import('../pages/roles/Form.vue'),
        meta: { requiresAuth: true, permission: 'role.create', layout: 'app' }
    },
    {
        path: '/roles/:id/edit',
        name: 'roles.edit',
        component: () => import('../pages/roles/Form.vue'),
        meta: { requiresAuth: true, permission: 'role.edit', layout: 'app' }
    },
    {
        path: '/profile',
        name: 'profile',
        component: () => import('../pages/Profile.vue'),
        meta: { requiresAuth: true, layout: 'app' }
    },
    {
        path: '/password-resets',
        name: 'password-resets.index',
        component: () => import('../pages/password-resets/Index.vue'),
        meta: { requiresAuth: true, layout: 'app' }
    },
    // Teacher Assignments
    {
        path: '/assignments',
        name: 'assignments.index',
        component: () => import('../pages/assignments/Index.vue'),
        meta: { requiresAuth: true, permission: 'user.view', layout: 'app' }
    },
    {
        path: '/assignments/reports',
        name: 'assignments.reports',
        component: () => import('../pages/assignments/Reports.vue'),
        meta: { requiresAuth: true, permission: 'user.view', layout: 'app' }
    },
    // Academic Management
    {
        path: '/academic-years',
        name: 'academic-years.index',
        component: () => import('../pages/academic-years/Index.vue'),
        meta: { requiresAuth: true, permission: 'academic_year.view', module: 'academics', layout: 'app' }
    },
    {
        path: '/classes',
        name: 'classes.index',
        component: () => import('../pages/classes/Index.vue'),
        meta: { requiresAuth: true, permission: 'class.view', module: 'academics', layout: 'app' }
    },
    {
        path: '/sections',
        name: 'sections.index',
        component: () => import('../pages/sections/Index.vue'),
        meta: { requiresAuth: true, permission: 'section.view', module: 'academics', layout: 'app' }
    },
    // Student Management
    {
        path: '/students',
        name: 'students.index',
        component: () => import('../pages/students/Index.vue'),
        meta: { requiresAuth: true, permission: 'student.view', module: 'students', layout: 'app' }
    },
    {
        path: '/students/create',
        name: 'students.create',
        component: () => import('../pages/students/Form.vue'),
        meta: { requiresAuth: true, permission: 'student.create', module: 'students', layout: 'app' }
    },
    {
        path: '/students/:id/edit',
        name: 'students.edit',
        component: () => import('../pages/students/Form.vue'),
        meta: { requiresAuth: true, permission: 'student.edit', module: 'students', layout: 'app' }
    },
    {
        path: '/students/:id',
        name: 'students.show',
        component: () => import('../pages/students/Show.vue'),
        meta: { requiresAuth: true, permission: 'student.view', module: 'students', layout: 'app' }
    },
    {
        path: '/students/promotion',
        name: 'students.promotion',
        component: () => import('../pages/students/Promotion.vue'),
        meta: { requiresAuth: true, permission: 'promotion.create', module: 'students', layout: 'app' }
    },
    // Subjects Module
    {
        path: '/subjects',
        name: 'subjects.index',
        component: () => import('../pages/subjects/Index.vue'),
        meta: { requiresAuth: true, permission: 'subject.view', module: 'subjects', layout: 'app' }
    },
    {
        path: '/academics/optional-subjects',
        name: 'subjects.optional',
        component: () => import('../pages/subjects/OptionalSubjects.vue'),
        meta: { requiresAuth: true, permission: 'subject.view', module: 'subjects', layout: 'app' }
    },
    // Attendance Module
    {
        path: '/attendance',
        name: 'attendance.index',
        component: () => import('../pages/attendance/Index.vue'),
        meta: { requiresAuth: true, permission: 'attendance.view', module: 'attendance', layout: 'app' }
    },
    {
        path: '/leaves',
        name: 'leaves.index',
        component: () => import('../pages/leaves/Index.vue'),
        meta: { requiresAuth: true, permission: 'attendance.view', module: 'attendance', layout: 'app' }
    },
    // Homework Module
    {
        path: '/homeworks',
        name: 'homeworks.index',
        component: () => import('../pages/homeworks/Index.vue'),
        meta: { requiresAuth: true, permission: 'homework.view', module: 'homework', layout: 'app' }
    },
    // Notices Module
    {
        path: '/notices',
        name: 'notices.index',
        component: () => import('../pages/notices/Index.vue'),
        meta: { requiresAuth: true, permission: 'notice.view', module: 'notices', layout: 'app' }
    },
    // Examinations Module
    {
        path: '/exams/grade-scales',
        name: 'exams.grade-scales',
        component: () => import('../pages/exams/GradeScales.vue'),
        meta: { requiresAuth: true, permission: 'exam.view', module: 'examinations', layout: 'app' }
    },
    {
        path: '/exams/exam-types',
        name: 'exams.exam-types',
        component: () => import('../pages/exams/ExamTypes.vue'),
        meta: { requiresAuth: true, permission: 'exam.view', module: 'examinations', layout: 'app' }
    },
    {
        path: '/exams/exams',
        name: 'exams.exams',
        component: () => import('../pages/exams/Exams.vue'),
        meta: { requiresAuth: true, permission: 'exam.view', module: 'examinations', layout: 'app' }
    },
    {
        path: '/exams/schedules',
        name: 'exams.schedules',
        component: () => import('../pages/exams/Schedules.vue'),
        meta: { requiresAuth: true, permission: ['exam_schedule.view', 'exam_schedule.create', 'exam_schedule.edit'], module: 'examinations', layout: 'app' }
    },
    {
        path: '/exams/marks',
        name: 'exams.marks',
        component: () => import('../pages/exams/Marks.vue'),
        meta: { requiresAuth: true, permission: ['marks.view', 'marks.create', 'marks.edit'], module: 'examinations', layout: 'app' }
    },
    {
        path: '/exams/results',
        name: 'exams.results',
        component: () => import('../pages/exams/Results.vue'),
        meta: { requiresAuth: true, permission: 'report_card.view', module: 'examinations', layout: 'app' }
    },
    {
        path: '/exams/report-card-setups',
        name: 'exams.report-card-setups',
        component: () => import('../pages/exams/ReportCardSetups.vue'),
        meta: { requiresAuth: true, permission: 'report_card_setup.manage', module: 'examinations', layout: 'app' }
    },
    // Fees Module
    {
        path: '/fees/fee-types',
        name: 'fees.fee-types',
        component: () => import('../pages/fees/FeeTypes.vue'),
        meta: { requiresAuth: true, permission: 'fee_type.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/fee-structures',
        name: 'fees.fee-structures',
        component: () => import('../pages/fees/FeeStructures.vue'),
        meta: { requiresAuth: true, permission: 'fee_structure.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/installments',
        name: 'fees.installments',
        component: () => import('../pages/fees/Installments.vue'),
        meta: { requiresAuth: true, permission: 'fee_structure.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/assignments',
        name: 'fees.assignments',
        component: () => import('../pages/fees/Assignments.vue'),
        meta: { requiresAuth: true, permission: 'fee_structure.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/discounts',
        name: 'fees.discounts',
        component: () => import('../pages/fees/Discounts.vue'),
        meta: { requiresAuth: true, permission: 'fee_collection.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/fine-rules',
        name: 'fees.fine-rules',
        component: () => import('../pages/fees/FineRules.vue'),
        meta: { requiresAuth: true, permission: 'fee_collection.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/collection',
        name: 'fees.collection',
        component: () => import('../pages/fees/Collection.vue'),
        meta: { requiresAuth: true, permission: 'fee_collection.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/receipts',
        name: 'fees.receipts',
        component: () => import('../pages/fees/Receipts.vue'),
        meta: { requiresAuth: true, permission: 'receipt.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/ledger',
        name: 'fees.ledger',
        component: () => import('../pages/fees/Ledger.vue'),
        meta: { requiresAuth: true, permission: 'ledger.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/fees/reports',
        name: 'fees.reports',
        component: () => import('../pages/fees/Reports.vue'),
        meta: { requiresAuth: true, permission: 'report.view', module: 'fees', layout: 'app' }
    },
    {
        path: '/forbidden',
        name: 'forbidden',
        component: () => import('../pages/errors/Forbidden.vue'),
        meta: { requiresAuth: true, layout: 'app' }
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/dashboard'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Check if store has loaded user details, if not try to fetch
    if (!authStore.user) {
        try {
            await authStore.fetchUser();
        } catch (e) {
            // Unauthenticated
        }
    }

    const isAuthenticated = authStore.authenticated;

    if (to.meta.requiresAuth) {
        if (!isAuthenticated) {
            return next({ name: 'login' });
        }

        // Check active module status
        if (to.meta.module && !authStore.hasModule(to.meta.module)) {
            return next({ name: 'forbidden' });
        }

        // Check Spatie permission
        if (to.meta.permission) {
            const perms = Array.isArray(to.meta.permission) ? to.meta.permission : [to.meta.permission];
            const hasAccess = perms.some(p => authStore.hasPermission(p));
            if (!hasAccess) {
                return next({ name: 'forbidden' });
            }
        }

        next();
    } else if (to.meta.guestOnly) {
        if (isAuthenticated) {
            return next({ name: 'dashboard' });
        }
        next();
    } else {
        next();
    }
});

export default router;
