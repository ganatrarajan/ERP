<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A comprehensive, multi-tenant School ERP System offering complete student management, fee collection, attendance tracking, and examination management.">
    <meta name="keywords" content="School ERP, Student Management, Fee Management, Attendance, School Software">
    
    <title>EduvoraX School ERP | Complete Institutional Management</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Outfit', sans-serif; }
        
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(241, 245, 249, 0.8);
        }
        
        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.12), transparent 45%),
                        radial-gradient(circle at bottom left, rgba(14, 165, 233, 0.12), transparent 45%);
        }
        
        .text-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #0ea5e9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .cta-gradient {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        }
        
        .feature-card {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.2);
        }

        /* Subtle blob pulse animations */
        @keyframes pulse-slow {
            0%, 100% { transform: scale(1) translate(0px, 0px); }
            50% { transform: scale(1.08) translate(8px, -8px); }
        }
        .animate-blob-slow {
            animation: pulse-slow 8s infinite ease-in-out;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden selection:bg-indigo-500 selection:text-white">

    <!-- Navigation -->
    <nav class="glass-nav fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-2.5 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-sky-500 flex items-center justify-center shadow-lg shadow-indigo-500/30 text-white font-black text-sm tracking-widest">
                        EVX
                    </div>
                    <span class="text-2xl font-extrabold tracking-tight text-slate-900">EduvoraX <span class="text-indigo-600">ERP</span></span>
                </div>
                
                <div class="hidden md:flex items-center space-x-10 font-bold text-sm text-slate-600">
                    <a href="#features" class="hover:text-indigo-600 transition-colors">Features</a>
                    <a href="#notifications" class="hover:text-indigo-600 transition-colors">FCM Center</a>
                    <a href="#modules" class="hover:text-indigo-600 transition-colors">All Modules</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="/login" class="text-sm font-bold text-slate-700 hover:text-indigo-600 transition-colors">Sign In</a>
                    <a href="/login" class="px-5 py-2.5 rounded-full bg-slate-900 text-white text-sm font-bold hover:bg-indigo-600 hover:shadow-lg hover:shadow-indigo-500/25 transition-all duration-300 transform hover:-translate-y-0.5">
                        Client Portal
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-44 lg:pb-28 hero-gradient overflow-hidden">
        <!-- Decorative blobs -->
        <div class="absolute top-20 right-10 w-96 h-96 bg-indigo-300/10 rounded-full mix-blend-multiply filter blur-3xl animate-blob-slow"></div>
        <div class="absolute top-40 -left-10 w-96 h-96 bg-sky-300/10 rounded-full mix-blend-multiply filter blur-3xl animate-blob-slow" style="animation-delay: 3s;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-black uppercase tracking-wider mb-8 shadow-sm">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                FCM Push Notifications & Instant Linking Live
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black text-slate-900 tracking-tight leading-[1.08] mb-6">
                Connect Your School's<br/>
                <span class="text-gradient">Digital Ecosystem</span>
            </h1>
            
            <p class="mt-6 text-lg md:text-xl text-slate-500 max-w-3xl mx-auto font-medium leading-relaxed mb-10">
                A unified multi-tenant SaaS School ERP platform. Manage academic records, automated fee structures, daily attendances, exam schedules, and keep students connected in real-time.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/login" class="px-8 py-4 w-full sm:w-auto rounded-full bg-gradient-to-r from-indigo-600 to-indigo-500 text-white text-base font-extrabold shadow-xl shadow-indigo-500/25 hover:shadow-indigo-500/35 hover:scale-105 transition-all duration-300">
                    Access Dashboard
                </a>
                <a href="#features" class="px-8 py-4 w-full sm:w-auto rounded-full bg-white border border-slate-200 text-slate-700 text-base font-bold shadow-sm hover:border-indigo-200 hover:text-indigo-600 transition-all duration-300">
                    Explore Features
                </a>
            </div>

            <!-- Dashboard Preview Mockup -->
            <div class="mt-16 relative mx-auto max-w-5xl">
                <div class="rounded-3xl border border-slate-200/50 bg-white/40 backdrop-blur-xl p-3 shadow-2xl overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-50 to-transparent z-10 h-32 bottom-0 top-auto pointer-events-none"></div>
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-inner flex flex-col">
                        <!-- Mock Header -->
                        <div class="h-12 border-b border-slate-100 bg-slate-50/50 flex items-center px-4 justify-between">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-rose-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            </div>
                            <div class="text-xs font-semibold text-slate-400">EduvoraX Web Admin Panel</div>
                            <div class="w-12"></div>
                        </div>
                        <!-- Mock Content Image -->
                        <img src="/images/dashboard_preview.png" alt="EduvoraX Dashboard UI Preview" class="w-full h-auto object-cover opacity-95" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Value Proposition / Statistics -->
    <section class="py-12 bg-white border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-indigo-600 tracking-tight">100%</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Data Isolation</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-indigo-600 tracking-tight">&lt; 1s</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">FCM Delivery</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-indigo-600 tracking-tight">5+</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Core ERP Modules</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-indigo-600 tracking-tight">PDF</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Receipt Engine</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Real-time FCM Notification Section -->
    <section id="notifications" class="py-24 bg-slate-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-indigo-500/5 mix-blend-color-burn"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/25 text-indigo-400 text-xs font-bold uppercase tracking-wider mb-6">
                        Real-time Communications
                    </div>
                    <h2 class="text-3xl md:text-5xl font-black tracking-tight leading-tight mb-6">
                        State-of-the-Art <br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-sky-400">FCM Notification Center</span>
                    </h2>
                    <p class="text-slate-400 text-lg leading-relaxed mb-8">
                        Our backend leverages the secure Firebase Admin SDK and standard JWT RS256 protocols to deliver instant push notifications straight to student and parent devices. Fully integrated with native device controls, caching, and automatic invalid token sweeping.
                    </p>
                    
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 mt-0.5">✓</span>
                            <div>
                                <h4 class="font-bold text-white text-base">Class & Section Smart Grouping</h4>
                                <p class="text-sm text-slate-400 mt-1">Assign homework, notice updates, or exam timetables. Only the active students mapped in those specific sections receive the push.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 mt-0.5">✓</span>
                            <div>
                                <h4 class="font-bold text-white text-base">Personalized Results & Fees Pushes</h4>
                                <p class="text-sm text-slate-400 mt-1">Instantly notifies individual students when their exam marks are posted or fee payments/receipts are registered on the ERP.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 mt-0.5">✓</span>
                            <div>
                                <h4 class="font-bold text-white text-base">Instant Mobile App Deep Linking</h4>
                                <p class="text-sm text-slate-400 mt-1">Clicking the notification directs the student directly to their Homework, Notice, Results, or Fees screen in the Flutter client.</p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="relative bg-slate-800/40 border border-slate-700/50 rounded-3xl p-8 backdrop-blur-xl">
                    <div class="absolute -top-6 -right-6 w-32 h-32 bg-sky-500/10 rounded-full blur-2xl"></div>
                    
                    <h3 class="text-lg font-bold mb-6 text-indigo-300 flex items-center gap-2">
                        <span class="flex h-2.5 w-2.5 rounded-full bg-indigo-400"></span>
                        Mobile Client Interactions
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Mock Push 1 -->
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-4 transform hover:scale-[1.02] transition-transform">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600/20 flex items-center justify-center text-indigo-400 shrink-0">
                                📚
                            </div>
                            <div>
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-sm text-white">New Homework Assigned</h5>
                                    <span class="text-[10px] text-slate-500">Just Now</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">Maths: Trigonometry exercises. Submission Date: 2026-06-30.</p>
                                <span class="inline-block px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20 text-[10px] font-semibold text-indigo-400 mt-2">Deep link: HomeworkScreen</span>
                            </div>
                        </div>

                        <!-- Mock Push 2 -->
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-4 transform hover:scale-[1.02] transition-transform">
                            <div class="w-10 h-10 rounded-xl bg-emerald-600/20 flex items-center justify-center text-emerald-400 shrink-0">
                                💵
                            </div>
                            <div>
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-sm text-white">Fee Payment Received</h5>
                                    <span class="text-[10px] text-slate-500">2m ago</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">Payment of ₹5,000 collected successfully for First Term Fees.</p>
                                <span class="inline-block px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-semibold text-emerald-400 mt-2">Deep link: FeesScreen</span>
                            </div>
                        </div>

                        <!-- Mock Push 3 -->
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-4 transform hover:scale-[1.02] transition-transform">
                            <div class="w-10 h-10 rounded-xl bg-rose-600/20 flex items-center justify-center text-rose-400 shrink-0">
                                📝
                            </div>
                            <div>
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-sm text-white">Exam Results Published</h5>
                                    <span class="text-[10px] text-slate-500">1h ago</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">Your marks for Science (Annual Exam 2026) have been published.</p>
                                <span class="inline-block px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/20 text-[10px] font-semibold text-rose-400 mt-2">Deep link: ResultsScreen</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section / Modules -->
    <section id="features" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight mb-4">Complete Suite of Features</h2>
                <p class="text-slate-500 font-medium text-lg">Centralize your entire operational workflow into one beautiful multi-tenant web application.</p>
            </div>

            <div id="modules" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Core Module: Student Info -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-6 font-bold text-xl">01</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Student Information</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-4">Detailed digital profile logs. Track admissions, parental associations, academic history, file attachments, and active enrollment status.</p>
                    </div>
                    <div class="text-xs font-bold text-indigo-600 uppercase tracking-widest">Admissions & Demographic Logs</div>
                </div>

                <!-- Core Module: Finance -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-6 font-bold text-xl">02</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Fee Management</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-4">Structured fee templates, custom discounts, automatic dynamic late fine calculations, collections records, and PDF receipt rendering.</p>
                    </div>
                    <div class="text-xs font-bold text-emerald-600 uppercase tracking-widest">Invoicing & Collections</div>
                </div>

                <!-- Core Module: Attendance -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center mb-6 font-bold text-xl">03</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Attendance Engine</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-4">Record student and staff daily logs. Integrated holiday exception logic and detailed analytics for identifying absenteeism patterns.</p>
                    </div>
                    <div class="text-xs font-bold text-sky-600 uppercase tracking-widest">Student & Staff Rosters</div>
                </div>

                <!-- Core Module: Examinations -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mb-6 font-bold text-xl">04</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Examinations & Marks</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-4">Exam schedules, syllabus assignments, raw marks entry, custom grade scale mapping, and auto-generated term report cards.</p>
                    </div>
                    <div class="text-xs font-bold text-rose-600 uppercase tracking-widest">Schedules, Marks & Grades</div>
                </div>

                <!-- Core Module: Homework -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-6 font-bold text-xl">05</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Homework & Notice Boards</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-4">Syllabus assignment tracking, document attachments storage, and target notices dispatched class-wise, section-wise, or school-wide.</p>
                    </div>
                    <div class="text-xs font-bold text-amber-600 uppercase tracking-widest">Homeworks & Bulletins</div>
                </div>

                <!-- Core Module: Multi-tenant -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center mb-6 font-bold text-xl">06</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">SaaS Isolation Architecture</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-4">Enforce absolute data boundaries. School isolation on all tables, custom roles with spatie-permissions, and central super administrator tools.</p>
                    </div>
                    <div class="text-xs font-bold text-violet-600 uppercase tracking-widest">Multi-Tenant Scoping</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-24 relative overflow-hidden cta-gradient text-white">
        <div class="absolute inset-0 bg-indigo-600/10 mix-blend-multiply"></div>
        <div class="max-w-4xl mx-auto px-4 relative z-10 text-center">
            <h2 class="text-4xl font-black mb-6 tracking-tight">Ready to modernise your operations?</h2>
            <p class="text-slate-400 text-lg mb-10 max-w-2xl mx-auto">Get started with our system to reduce administrative workloads and keep your entire school connected instantly.</p>
            <a href="/login" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-indigo-500 text-white font-extrabold hover:bg-indigo-400 hover:scale-105 transition-all duration-300 shadow-xl shadow-indigo-500/20">
                Log into Dashboard
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 py-12 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-black text-xs tracking-wider">
                    EVX
                </div>
                <span class="text-xl font-bold text-white tracking-tight">EduvoraX</span>
            </div>
            <p class="text-slate-500 text-sm font-medium">
                &copy; {{ date('Y') }} School Management Systems. All rights reserved.
            </p>
            <div class="flex gap-6 text-sm text-slate-500">
                <a href="#" class="hover:text-indigo-400 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-indigo-400 transition-colors">Terms of Service</a>
            </div>
        </div>
    </footer>

</body>
</html>
