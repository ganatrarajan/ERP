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
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.3);
        }
        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(14, 165, 233, 0.15), transparent 40%);
        }
        .text-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .feature-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -15px rgba(79, 70, 229, 0.15);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden selection:bg-indigo-500 selection:text-white">

    <!-- Navigation -->
    <nav class="glass-nav fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-2 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-sky-500 flex items-center justify-center shadow-lg shadow-indigo-500/30 text-white font-black text-sm tracking-widest">
                        EVX
                    </div>
                    <span class="text-2xl font-black tracking-tight text-slate-900">EduvoraX <span class="text-indigo-600">ERP</span></span>
                </div>
                
                <div class="hidden md:flex items-center space-x-8 font-semibold text-sm text-slate-600">
                    <a href="#features" class="hover:text-indigo-600 transition-colors">Features</a>
                    <a href="#modules" class="hover:text-indigo-600 transition-colors">Modules</a>
                    <a href="#contact" class="hover:text-indigo-600 transition-colors">Contact</a>
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
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 hero-gradient overflow-hidden">
        <!-- Decorative blobs -->
        <div class="absolute top-20 right-10 w-72 h-72 bg-indigo-400/20 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute top-40 -left-10 w-72 h-72 bg-sky-400/20 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-sm font-bold mb-8 shadow-sm">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                Version 2.0 Now Available
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black text-slate-900 tracking-tight leading-[1.1] mb-6">
                Transform Your School<br/>
                <span class="text-gradient">Digital Ecosystem</span>
            </h1>
            
            <p class="mt-6 text-lg md:text-xl text-slate-500 max-w-3xl mx-auto font-medium leading-relaxed mb-10">
                A unified platform to manage admissions, academic tracking, fee collections, and powerful data analytics. Designed for multi-tenant modern institutions.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/login" class="px-8 py-4 w-full sm:w-auto rounded-full bg-gradient-to-r from-indigo-600 to-indigo-500 text-white text-base font-extrabold shadow-xl shadow-indigo-500/30 hover:shadow-indigo-500/40 hover:scale-105 transition-all duration-300">
                    Access Dashboard
                </a>
                <a href="#features" class="px-8 py-4 w-full sm:w-auto rounded-full bg-white border border-slate-200 text-slate-700 text-base font-bold shadow-sm hover:border-slate-300 hover:bg-slate-50 transition-all duration-300">
                    Explore Features
                </a>
            </div>

            <!-- Dashboard Preview Mockup -->
            <div class="mt-20 relative mx-auto max-w-5xl">
                <div class="rounded-2xl border border-slate-200/50 bg-white/50 backdrop-blur-xl p-2 shadow-2xl overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-50 to-transparent z-10 h-32 bottom-0 top-auto"></div>
                    <div class="rounded-xl border border-slate-100 bg-white overflow-hidden shadow-inner flex flex-col">
                        <!-- Mock Header -->
                        <div class="h-12 border-b border-slate-100 bg-slate-50/50 flex items-center px-4 gap-2">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-rose-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            </div>
                        </div>
                        <!-- Mock Content Image -->
                        <img src="/images/dashboard_preview.png" alt="EduvoraX Dashboard Preview" class="w-full h-auto object-cover opacity-90" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mb-4">Everything you need to run your institution</h2>
                <p class="text-slate-500 font-medium text-lg">Ditch the spreadsheets and legacy systems. EduvoraX centralizes your entire operational workflow into one beautiful interface.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Core Module: Student Info -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Student Information</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Complete digital profiles. Track admissions, demographics, parental details, academic history, and disciplinary records effortlessly.</p>
                </div>

                <!-- Core Module: Finance -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Fee Management</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Automated fee structures, recurring installments, custom discounts, late fines, and beautiful PDF receipt generation.</p>
                </div>

                <!-- Core Module: Attendance -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl">
                    <div class="w-14 h-14 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Attendance Tracking</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Daily robust attendance for both students and staff. Identify chronic absenteeism with built-in analytical reports.</p>
                </div>

                <!-- Core Module: Examinations -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl">
                    <div class="w-14 h-14 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Examinations & Results</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Dynamic grade scales, exam scheduling, subject mark assignments, and automatic aggregated report card generation.</p>
                </div>

                <!-- Core Module: Academics -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl">
                    <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Academic Management</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Structure classes, sections, and subjects globally. Configure academic sessions and promote students sequentially across years.</p>
                </div>

                <!-- Core Module: Multi-tenant -->
                <div class="feature-card bg-slate-50 border border-slate-100 p-8 rounded-3xl">
                    <div class="w-14 h-14 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Multi-tenant Architecture</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Manage a single school or an entire district. Strict data isolation via school scoping with centralized Super Admin controls.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-24 relative overflow-hidden bg-slate-900">
        <div class="absolute inset-0 bg-indigo-600/10 mix-blend-multiply"></div>
        <div class="max-w-4xl mx-auto px-4 relative z-10 text-center">
            <h2 class="text-4xl font-black text-white mb-6 tracking-tight">Ready to modernize your operations?</h2>
            <p class="text-slate-400 text-lg mb-10 max-w-2xl mx-auto">Join the institutions using our platform to drastically reduce administrative workload and gain real-time insights.</p>
            <a href="/login" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-indigo-500 text-white font-extrabold hover:bg-indigo-400 hover:scale-105 transition-all duration-300 shadow-xl shadow-indigo-500/20">
                Log into Dashboard
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-slate-950 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
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
                <a href="#" class="hover:text-indigo-400 transition-colors">Contact Support</a>
            </div>
        </div>
    </footer>

</body>
</html>
