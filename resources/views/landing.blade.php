@php
    $whatsappNumber = \App\Models\SystemSetting::getSetting('whatsapp_number', '919999999999');
    $contactPhone = \App\Models\SystemSetting::getSetting('contact_phone', '+91 99999 99999');
    $contactEmail = \App\Models\SystemSetting::getSetting('contact_email', 'support@eduvorax.com');
    $foundingOfferText = \App\Models\SystemSetting::getSetting('founding_offer_text', 'Get EduvoraX school management software free for your first year as part of our founding-school program.');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- SEO Meta Tags -->
    <title>EduvoraX — Simple School Management Software</title>
    <meta name="description" content="EduvoraX helps schools manage students, fees, attendance, exams, results and parent communication from one simple platform.">
    <meta name="keywords" content="EduvoraX, School Management Software, School ERP, Student Management, Fee Management, Gujarat School Software, Parent App">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="EduvoraX — Simple School Management Software">
    <meta property="og:description" content="Manage students, fees, attendance, exams, results and parent communication from one simple school management platform.">
    <meta property="og:site_name" content="EduvoraX">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Outfit', sans-serif; }
        
        .glass-nav {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .hero-bg {
            background: radial-gradient(circle at 75% 20%, rgba(99, 102, 241, 0.08), transparent 45%),
                        radial-gradient(circle at 20% 80%, rgba(14, 165, 233, 0.08), transparent 45%);
        }
        
        .text-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #0284c7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card-hover {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(79, 70, 229, 0.1);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden selection:bg-indigo-600 selection:text-white">

    <!-- Top Announcement Bar -->
    <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white text-xs font-semibold py-2.5 px-4 text-center border-b border-indigo-700/50 flex items-center justify-center gap-2">
        <span class="inline-block px-2 py-0.5 rounded-full bg-amber-400 text-slate-950 font-extrabold text-[10px] uppercase tracking-wider">Founding Offer</span>
        <span>{{ $foundingOfferText }}</span>
        <button onclick="openDemoModal()" class="underline font-bold text-indigo-200 hover:text-white ml-1">Claim Offer →</button>
    </div>

    <!-- Navigation Header -->
    <nav class="glass-nav sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Brand Logo -->
                <a href="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white font-black text-sm tracking-wider flex items-center justify-center shadow-md shadow-indigo-500/20 group-hover:bg-indigo-700 transition-colors">
                        EVX
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-extrabold tracking-tight text-slate-900">EduvoraX</span>
                        <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest -mt-1">School Management</span>
                    </div>
                </a>
                
                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                    <a href="#why-us" class="hover:text-indigo-600 transition-colors">Why EduvoraX</a>
                    <a href="#features" class="hover:text-indigo-600 transition-colors">Features</a>
                    <a href="#how-it-works" class="hover:text-indigo-600 transition-colors">How It Works</a>
                    <a href="#parent-app" class="hover:text-indigo-600 transition-colors">Parent App</a>
                    <a href="#who-it-is-for" class="hover:text-indigo-600 transition-colors">For Schools</a>
                    <a href="#faq" class="hover:text-indigo-600 transition-colors">FAQ</a>
                </div>

                <!-- Header Action Buttons -->
                <div class="flex items-center gap-3">
                    <a href="https://wa.me/{{ $whatsappNumber }}?text=Hello%20EduvoraX%20Team%2C%20I%20would%20like%20to%20know%20more%20about%20EduvoraX%20school%20software." target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-full border border-emerald-500/30 text-emerald-700 bg-emerald-50 text-xs font-bold hover:bg-emerald-100 transition-colors">
                        <span>💬 WhatsApp</span>
                    </a>
                    <a href="/login" class="text-xs font-bold text-slate-700 hover:text-indigo-600 px-3 py-2 transition-colors">
                        Sign In
                    </a>
                    <button onclick="openDemoModal()" class="px-5 py-2.5 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20 hover:shadow-lg transition-all">
                        Book a Free Demo
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- 1. HERO SECTION -->
    <section class="relative pt-16 pb-20 lg:pt-24 lg:pb-28 hero-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            
            <!-- Offer Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 border border-indigo-200/80 text-indigo-800 text-xs font-bold uppercase tracking-wider mb-6 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Founding Schools: 1st Year FREE
            </div>
            
            <!-- Headline -->
            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black text-slate-900 tracking-tight leading-[1.1] mb-6">
                Simple School Management.<br/>
                <span class="text-gradient">Everything in One Place.</span>
            </h1>
            
            <!-- Supporting text -->
            <p class="text-slate-600 text-base sm:text-lg md:text-xl max-w-3xl mx-auto font-normal leading-relaxed mb-8">
                Manage students, fees, attendance, exams, results and parent communication from one simple school management platform built for modern schools.
            </p>
            
            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-14">
                <button onclick="openDemoModal()" class="w-full sm:w-auto px-8 py-4 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-xl shadow-indigo-600/25 transition-all transform hover:-translate-y-0.5">
                    Book a Free Demo
                </button>
                <a href="https://wa.me/{{ $whatsappNumber }}?text=Hello%20EduvoraX%20Team%2C%20I%20want%20to%20book%20a%20free%20demo%20for%20my%20school." target="_blank" class="w-full sm:w-auto px-8 py-4 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                    <span>💬 WhatsApp Us</span>
                </a>
            </div>

            <!-- Product Dashboard Visual Mockup -->
            <div class="relative mx-auto max-w-5xl rounded-2xl border border-slate-300/80 bg-slate-900 p-2 sm:p-3 shadow-2xl text-left">
                <div class="rounded-xl border border-slate-800 bg-slate-950 overflow-hidden">
                    <!-- Browser Bar -->
                    <div class="h-10 bg-slate-900 border-b border-slate-800 px-4 flex items-center justify-between">
                        <div class="flex gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                        </div>
                        <span class="text-xs font-mono text-slate-400">EduvoraX Admin Console — Dashboard Overview</span>
                        <span class="text-xs text-indigo-400 font-bold hidden sm:inline-block">Live Active Session</span>
                    </div>
                    
                    <!-- Dashboard Visual Content -->
                    <div class="p-6 text-slate-100 space-y-6">
                        <!-- Top Stat Cards Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-xl">
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Total Students</p>
                                <h3 class="text-2xl font-extrabold text-white mt-1">450</h3>
                                <p class="text-[10px] text-emerald-400 font-medium mt-1">Active Enrolled</p>
                            </div>
                            <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-xl">
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Today's Attendance</p>
                                <h3 class="text-2xl font-extrabold text-emerald-400 mt-1">95.4%</h3>
                                <p class="text-[10px] text-slate-400 font-medium mt-1">Present Today</p>
                            </div>
                            <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-xl">
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Fees Collected</p>
                                <h3 class="text-2xl font-extrabold text-indigo-400 mt-1">₹3,45,000</h3>
                                <p class="text-[10px] text-indigo-300 font-medium mt-1">Term Collections</p>
                            </div>
                            <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-xl">
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Parent Mobile App</p>
                                <h3 class="text-2xl font-extrabold text-white mt-1">Connected</h3>
                                <p class="text-[10px] text-emerald-400 font-medium mt-1">FCM Push Enabled</p>
                            </div>
                        </div>

                        <!-- Feature Panels -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2 bg-slate-900/90 border border-slate-800 p-5 rounded-xl space-y-3">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Fee Collection Overview</h4>
                                    <span class="text-[10px] bg-indigo-950 border border-indigo-800 text-indigo-300 px-2 py-0.5 rounded font-mono">Academic Year 2025-2026</span>
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between items-center bg-slate-950 p-2.5 rounded-lg border border-slate-800">
                                        <span class="text-slate-300">Class 10 - Section A (Term 1 Fees)</span>
                                        <span class="font-mono text-emerald-400 font-bold">₹1,25,000 Collected</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-slate-950 p-2.5 rounded-lg border border-slate-800">
                                        <span class="text-slate-300">Class 9 - Section B (Term 1 Fees)</span>
                                        <span class="font-mono text-emerald-400 font-bold">₹98,000 Collected</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-slate-950 p-2.5 rounded-lg border border-slate-800">
                                        <span class="text-slate-300">Class 8 - Section A (Term 1 Fees)</span>
                                        <span class="font-mono text-emerald-400 font-bold">₹1,22,000 Collected</span>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-900/90 border border-slate-800 p-5 rounded-xl space-y-3">
                                <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Quick Actions</h4>
                                <div class="space-y-2 text-xs font-medium">
                                    <div class="p-2 bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 rounded-lg flex items-center justify-between">
                                        <span>✓ Mark Daily Attendance</span>
                                        <span class="text-[10px] bg-indigo-600 text-white px-2 py-0.5 rounded">Ready</span>
                                    </div>
                                    <div class="p-2 bg-slate-950 text-slate-300 border border-slate-800 rounded-lg flex items-center justify-between">
                                        <span>📄 Generate Fee Receipts</span>
                                        <span class="text-[10px] text-slate-400">PDF Print</span>
                                    </div>
                                    <div class="p-2 bg-slate-950 text-slate-300 border border-slate-800 rounded-lg flex items-center justify-between">
                                        <span>📊 Issue Term Report Cards</span>
                                        <span class="text-[10px] text-slate-400">Auto Grade</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. TRUST / VALUE SECTION -->
    <section id="why-us" class="py-20 bg-white border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Built For Schools</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mt-2">Why Schools Choose EduvoraX</h2>
                <p class="text-slate-600 mt-3 text-base">Designed to remove daily administrative headaches and keep your entire school organized effortlessly.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <!-- Value 1 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 card-hover text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 font-bold text-xl flex items-center justify-center mx-auto">
                        📋
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">Reduce Paperwork</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Replace manual registers and paper files with clean digital records for students, fees, and attendance.</p>
                </div>

                <!-- Value 2 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 card-hover text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-sky-100 text-sky-600 font-bold text-xl flex items-center justify-center mx-auto">
                        🗂️
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">Keep Info Organized</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Maintain all student profiles, class rosters, and academic records securely in one central database.</p>
                </div>

                <!-- Value 3 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 card-hover text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 font-bold text-xl flex items-center justify-center mx-auto">
                        ⚙️
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">Simplify Admin</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Automate fee collection receipts, dynamic late fines, and class promotion workflows easily.</p>
                </div>

                <!-- Value 4 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 card-hover text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 font-bold text-xl flex items-center justify-center mx-auto">
                        💬
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">Improve Parent Trust</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Send instant push notifications to parents regarding attendance, homework, notices, and fee receipts.</p>
                </div>

                <!-- Value 5 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 card-hover text-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 font-bold text-xl flex items-center justify-center mx-auto">
                        💻
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">Access Anywhere</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">View important school information securely from any computer, tablet, or mobile phone.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. FEATURES SECTION -->
    <section id="features" class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Verified System Features</span>
                <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight mt-2">Everything Your School Needs</h2>
                <p class="text-slate-600 mt-3 text-base">Complete operational modules designed specifically for school administrators, teachers, students, and parents.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Student Management -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-lg">👨‍🎓</div>
                    <h3 class="text-xl font-bold text-slate-900">Student Management</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Complete digital profiles with Admission No, GR No, Aadhaar/UDISE details, parent contacts, academic year history, and photo uploads.</p>
                </div>

                <!-- Feature 2: Parent & Student App -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-lg">📱</div>
                    <h3 class="text-xl font-bold text-slate-900">Parent & Student Mobile App</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Dedicated Flutter mobile application for parents to check attendance, view homework, receive notices, pay fees online, and download PDF receipts.</p>
                </div>

                <!-- Feature 3: Attendance -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center font-bold text-lg">📅</div>
                    <h3 class="text-xl font-bold text-slate-900">Attendance System</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Fast daily attendance marking for students and staff with holiday calendars, monthly attendance summaries, and absenteeism tracking.</p>
                </div>

                <!-- Feature 4: Fees Management -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-lg">💳</div>
                    <h3 class="text-xl font-bold text-slate-900">Fees Management</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Flexible fee structures, installment setup, discounts, automatic late fine rules, printable PDF receipts, and Razorpay online gateway integration.</p>
                </div>

                <!-- Feature 5: Exams & Results -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-lg">📝</div>
                    <h3 class="text-xl font-bold text-slate-900">Exams & Results</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Manage exam schedules, subject mark entries, customizable grading scales, student rank calculations, admit cards, and term report card printing.</p>
                </div>

                <!-- Feature 6: School Communication -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-lg">📢</div>
                    <h3 class="text-xl font-bold text-slate-900">School Communication</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Publish homework assignments with document attachments, section-wise notice bulletins, and push notification delivery via Firebase (FCM).</p>
                </div>

                <!-- Feature 7: Classes & Divisions -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center font-bold text-lg">🏫</div>
                    <h3 class="text-xl font-bold text-slate-900">Classes & Divisions</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Structure classes, sections, academic years, and subject allocations seamlessly with automated end-of-year student promotion management.</p>
                </div>

                <!-- Feature 8: Teacher & Staff Management -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg">👩‍🏫</div>
                    <h3 class="text-xl font-bold text-slate-900">Staff & Role Permissions</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Teacher profiles, teacher class assignments, employee documents, and role-based access control for security across your staff team.</p>
                </div>

                <!-- Feature 9: Dashboard & Reports -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm card-hover space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-lg">📊</div>
                    <h3 class="text-xl font-bold text-slate-900">Reports & Dashboard</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Real-time admin dashboard metrics, fee collection ledgers, unpaid dues statements, attendance reports, and academic performance data.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. HOW IT WORKS -->
    <section id="how-it-works" class="py-20 bg-white border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Simple 3-Step Onboarding</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mt-2">How It Works</h2>
                <p class="text-slate-600 mt-3 text-base">Getting your school started with EduvoraX is simple and hassle-free.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Step 1 -->
                <div class="bg-slate-50 rounded-2xl p-8 border border-slate-200/80 text-center space-y-4 relative">
                    <div class="w-12 h-12 rounded-full bg-indigo-600 text-white font-extrabold text-lg flex items-center justify-center mx-auto shadow-md">
                        1
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Book a Free Demo</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Fill out our quick demo request form or reach out to us on WhatsApp. Our team will guide you through a live walkthrough.</p>
                </div>

                <!-- Step 2 -->
                <div class="bg-slate-50 rounded-2xl p-8 border border-slate-200/80 text-center space-y-4 relative">
                    <div class="w-12 h-12 rounded-full bg-indigo-600 text-white font-extrabold text-lg flex items-center justify-center mx-auto shadow-md">
                        2
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Set Up Your School</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">We assist you in configuring your school profile, academic year, classes, sections, fee structures, and staff accounts.</p>
                </div>

                <!-- Step 3 -->
                <div class="bg-slate-50 rounded-2xl p-8 border border-slate-200/80 text-center space-y-4 relative">
                    <div class="w-12 h-12 rounded-full bg-indigo-600 text-white font-extrabold text-lg flex items-center justify-center mx-auto shadow-md">
                        3
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Start Managing Your School</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Log in to your admin console, share parent app credentials, and manage daily school operations with ease.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. PARENT APP SECTION -->
    <section id="parent-app" class="py-24 bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-950 text-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-6 space-y-6">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-bold uppercase tracking-wider">
                        <span>📱</span> Mobile Application
                    </div>
                    
                    <h2 class="text-3xl md:text-5xl font-black tracking-tight leading-tight">
                        Keep Parents Connected<br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-sky-400">To Their Child's Schooling</span>
                    </h2>
                    
                    <p class="text-slate-300 text-base md:text-lg leading-relaxed">
                        Keep parents connected with important school updates, attendance, fees, results and other available information directly through the EduvoraX mobile app.
                    </p>

                    <div class="space-y-4 pt-2">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-1 font-bold text-xs">✓</div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Attendance Records</h4>
                                <p class="text-xs text-slate-400">Parents check daily attendance status and monthly present/absent summary.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-1 font-bold text-xs">✓</div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Fee Payment & Receipts</h4>
                                <p class="text-xs text-slate-400">View upcoming fee installments, pay online securely, and download official PDF receipts.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-1 font-bold text-xs">✓</div>
                            <div>
                                <h4 class="font-bold text-white text-sm">Homework & School Bulletins</h4>
                                <p class="text-xs text-slate-400">Receive class assignments with downloadable document attachments and notice circulars.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Phone Mockup -->
                <div class="lg:col-span-6 flex justify-center">
                    <div class="w-72 sm:w-80 rounded-[40px] border-4 border-slate-700 bg-slate-900 p-3 shadow-2xl relative">
                        <!-- Screen Header -->
                        <div class="rounded-[30px] bg-slate-950 p-4 border border-slate-800 text-left space-y-4 min-h-[460px]">
                            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                                <div>
                                    <h5 class="text-xs font-bold text-white">EduvoraX Student App</h5>
                                    <p class="text-[10px] text-slate-400">Student Profile & Updates</p>
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            </div>

                            <!-- Student Info Card -->
                            <div class="bg-indigo-950/60 border border-indigo-800/60 p-3 rounded-xl space-y-1">
                                <p class="text-[10px] text-indigo-300 font-bold uppercase">Student Profile</p>
                                <h6 class="text-sm font-bold text-white">Chintan Metadiya</h6>
                                <p class="text-[11px] text-slate-300">Class 10 - Section A | GR: 1042</p>
                            </div>

                            <!-- Quick Stats -->
                            <div class="grid grid-cols-2 gap-2 text-center">
                                <div class="bg-slate-900 p-2.5 rounded-lg border border-slate-800">
                                    <span class="text-[9px] text-slate-400 block uppercase">Attendance</span>
                                    <strong class="text-xs text-emerald-400 font-bold">96% Present</strong>
                                </div>
                                <div class="bg-slate-900 p-2.5 rounded-lg border border-slate-800">
                                    <span class="text-[9px] text-slate-400 block uppercase">Term Fee</span>
                                    <strong class="text-xs text-amber-400 font-bold">₹0 Due</strong>
                                </div>
                            </div>

                            <!-- App Updates List -->
                            <div class="space-y-2 pt-1">
                                <div class="bg-slate-900 p-2.5 rounded-lg border border-slate-800 flex items-center justify-between text-[11px]">
                                    <div>
                                        <span class="font-bold text-white block">📚 Maths Homework</span>
                                        <span class="text-[9px] text-slate-400">Algebra Sheet Attached</span>
                                    </div>
                                    <span class="text-[9px] bg-indigo-900 text-indigo-300 px-1.5 py-0.5 rounded">View</span>
                                </div>
                                <div class="bg-slate-900 p-2.5 rounded-lg border border-slate-800 flex items-center justify-between text-[11px]">
                                    <div>
                                        <span class="font-bold text-white block">📄 Term 1 Report Card</span>
                                        <span class="text-[9px] text-slate-400">Grade: A+ (Passed)</span>
                                    </div>
                                    <span class="text-[9px] bg-emerald-900 text-emerald-300 px-1.5 py-0.5 rounded">PDF</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 6. ADMIN DASHBOARD SECTION -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Powerful Web Portal</span>
                <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight mt-2">Built For School Owners & Administrators</h2>
                <p class="text-slate-600 mt-3 text-base">Clear oversight into daily attendance, fee collections, student records, and academic schedules.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Portal Feature 1 -->
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200/80 space-y-3">
                    <div class="text-2xl">📊</div>
                    <h3 class="text-lg font-bold text-slate-900">Real-Time Overview</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Instant dashboard metrics detailing total active student count, daily attendance percentages, and fee collections.</p>
                </div>

                <!-- Portal Feature 2 -->
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200/80 space-y-3">
                    <div class="text-2xl">💰</div>
                    <h3 class="text-lg font-bold text-slate-900">Fee Collection Ledger</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Track paid installments, overdue balances, discounts, and print branded PDF receipts directly for parents.</p>
                </div>

                <!-- Portal Feature 3 -->
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200/80 space-y-3">
                    <div class="text-2xl">🏆</div>
                    <h3 class="text-lg font-bold text-slate-900">Report Card Generator</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Generate term report cards automatically based on entered exam marks, attendance stats, and grade scales.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. WHO IS EDUVORAX FOR? -->
    <section id="who-it-is-for" class="py-20 bg-slate-50 border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Tailored Solutions</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mt-2">Who Is EduvoraX For?</h2>
                <p class="text-slate-600 mt-3 text-base">EduvoraX is tailored specifically for small and medium-sized educational institutions.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Category 1 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 text-center space-y-3 card-hover">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 font-bold text-2xl flex items-center justify-center mx-auto">
                        🏫
                    </div>
                    <h3 class="font-bold text-slate-900 text-lg">Primary & Secondary Schools</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Suitable for schools managing K-12 classes, multiple divisions, and term-wise exam schedules.</p>
                </div>

                <!-- Category 2 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 text-center space-y-3 card-hover">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 font-bold text-2xl flex items-center justify-center mx-auto">
                        🚩
                    </div>
                    <h3 class="font-bold text-slate-900 text-lg">Gujarati Medium Schools</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Designed with local Gujarati/Indian school structures, GR registers, and fee collection patterns in mind.</p>
                </div>

                <!-- Category 3 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 text-center space-y-3 card-hover">
                    <div class="w-12 h-12 rounded-xl bg-sky-100 text-sky-600 font-bold text-2xl flex items-center justify-center mx-auto">
                        🌐
                    </div>
                    <h3 class="font-bold text-slate-900 text-lg">English Medium Schools</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Ideal for English medium institutions seeking digital parent communication and instant PDF receipts.</p>
                </div>

                <!-- Category 4 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 text-center space-y-3 card-hover">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 font-bold text-2xl flex items-center justify-center mx-auto">
                        📈
                    </div>
                    <h3 class="font-bold text-slate-900 text-lg">Small & Medium Schools</h3>
                    <p class="text-slate-600 text-xs leading-relaxed">Affordable, straightforward software for schools looking to digitize without bloated corporate pricing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. FOUNDING SCHOOL OFFER SECTION -->
    <section class="py-20 bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 text-center relative z-10 space-y-6">
            <span class="inline-block px-4 py-1.5 rounded-full bg-amber-400 text-slate-950 text-xs font-black uppercase tracking-wider">
                Exclusive Founding Offer
            </span>
            <h2 class="text-3xl md:text-5xl font-black tracking-tight leading-tight">
                Launch Your School Digitally — 1st Year FREE
            </h2>
            <p class="text-indigo-100 text-base md:text-lg max-w-3xl mx-auto leading-relaxed">
                {{ $foundingOfferText }} We are inviting a limited number of schools to become founding EduvoraX partners.
            </p>
            <div class="pt-4 flex flex-col sm:flex-row justify-center items-center gap-4">
                <button onclick="openDemoModal()" class="w-full sm:w-auto px-8 py-4 rounded-full bg-white text-indigo-950 hover:bg-slate-100 font-extrabold text-sm uppercase tracking-wider shadow-xl transition-all">
                    Book Free Demo
                </button>
                <a href="https://wa.me/{{ $whatsappNumber }}?text=Hello%20EduvoraX%20Team%2C%20I%20want%20to%20apply%20for%20the%20Founding%20School%201st%20Year%20Free%20program." target="_blank" class="w-full sm:w-auto px-8 py-4 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm uppercase tracking-wider shadow-lg transition-all flex items-center justify-center gap-2">
                    <span>💬 WhatsApp Us</span>
                </a>
            </div>
            <p class="text-[11px] text-indigo-200/80 pt-2">
                * Note: The founding school offer is available for a limited number of participating institutions. Subscription renewal terms will be communicated transparently prior to the end of the first year.
            </p>
        </div>
    </section>

    <!-- 9. FAQ SECTION -->
    <section id="faq" class="py-24 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Got Questions?</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mt-2">Frequently Asked Questions</h2>
                <p class="text-slate-600 mt-3 text-base">Find quick answers to common questions about EduvoraX.</p>
            </div>

            <div class="space-y-4">
                <!-- FAQ 1 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>What is EduvoraX?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        EduvoraX is a comprehensive school management platform that helps schools handle student profiles, fee collections, attendance, examinations, report cards, and parent communication from one simple system.
                    </p>
                </details>

                <!-- FAQ 2 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>Which schools can use EduvoraX?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        EduvoraX is tailored for small and medium-sized primary, secondary, Gujarati medium, and English medium schools seeking a modern, affordable digital management solution.
                    </p>
                </details>

                <!-- FAQ 3 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>Does EduvoraX have a parent app?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        Yes! EduvoraX includes a dedicated mobile application for parents and students to track attendance, view homework, check notices, pay fees online, and download report cards.
                    </p>
                </details>

                <!-- FAQ 4 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>Can we get a demo?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        Absolutely! Click the "Book a Free Demo" button or message us on WhatsApp. Our team will arrange a free live walkthrough of the platform for your school management team.
                    </p>
                </details>

                <!-- FAQ 5 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>Is the first year really free?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        Yes. Under our Founding Schools initiative, selected schools receive complete access to the EduvoraX school management platform free for their first year.
                    </p>
                </details>

                <!-- FAQ 6 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>What happens after the first year?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        Before your first year subscription period ends, our team will communicate transparent renewal pricing options for your school so you can continue uninterrupted.
                    </p>
                </details>

                <!-- FAQ 7 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>Do you help with school setup?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        Yes, our technical team assists your school with initial configuration including academic years, class divisions, fee structures, and staff accounts.
                    </p>
                </details>

                <!-- FAQ 8 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>Can school data be imported?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        Yes, student rosters and initial class details can be imported during setup to help your school transition quickly.
                    </p>
                </details>

                <!-- FAQ 9 -->
                <details class="group bg-slate-50 p-6 rounded-2xl border border-slate-200/80 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center font-bold text-slate-900 text-base md:text-lg cursor-pointer">
                        <span>How can we contact EduvoraX?</span>
                        <span class="transition group-open:rotate-180 text-indigo-600">▼</span>
                    </summary>
                    <p class="mt-4 text-slate-600 text-sm leading-relaxed">
                        You can reach us directly via WhatsApp, call us at {{ $contactPhone }}, email us at <a href="mailto:{{ $contactEmail }}" class="text-indigo-600 underline">{{ $contactEmail }}</a>, or submit a demo request form on our website.
                    </p>
                </details>
            </div>
        </div>
    </section>

    <!-- 10. CONTACT / DEMO SECTION -->
    <section id="demo" class="py-24 bg-slate-50 border-t border-slate-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl p-8 md:p-12 border border-slate-200 shadow-xl text-center space-y-8">
                <div class="space-y-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Get Started Today</span>
                    <h2 class="text-3xl md:text-4xl font-black text-slate-900">
                        Ready to simplify your school's daily management?
                    </h2>
                    <p class="text-slate-600 text-base max-w-xl mx-auto">
                        Book a free demo today or chat with us on WhatsApp to discover how EduvoraX can digitize your school administration.
                    </p>
                </div>

                <!-- Demo Buttons -->
                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                    <button onclick="openDemoModal()" class="w-full sm:w-auto px-8 py-4 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm uppercase tracking-wider shadow-lg shadow-indigo-600/25 transition-all">
                        Book a Free Demo
                    </button>
                    <a href="https://wa.me/{{ $whatsappNumber }}?text=Hello%20EduvoraX%20Team%2C%20I%20would%20like%20to%20book%20a%20free%20demo%20for%20my%20school." target="_blank" class="w-full sm:w-auto px-8 py-4 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm uppercase tracking-wider shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                        <span>💬 WhatsApp Us</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 11. FOOTER -->
    <footer class="bg-slate-950 text-white pt-16 pb-12 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-slate-900">
                <!-- Col 1: Brand -->
                <div class="space-y-4 md:col-span-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white font-black text-sm tracking-wider flex items-center justify-center">
                            EVX
                        </div>
                        <span class="text-2xl font-extrabold text-white">EduvoraX</span>
                    </div>
                    <p class="text-xs text-slate-400 max-w-sm leading-relaxed">
                        Simple school management software designed for primary, secondary, Gujarati medium, and English medium schools across Gujarat and India.
                    </p>
                    <p class="text-[11px] text-indigo-400 font-semibold">
                        First Year FREE for Founding Schools
                    </p>
                </div>

                <!-- Col 2: Navigation -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Navigation</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="/" class="hover:text-indigo-400 transition-colors">Home</a></li>
                        <li><a href="#why-us" class="hover:text-indigo-400 transition-colors">Why EduvoraX</a></li>
                        <li><a href="#features" class="hover:text-indigo-400 transition-colors">System Features</a></li>
                        <li><a href="#how-it-works" class="hover:text-indigo-400 transition-colors">How It Works</a></li>
                        <li><a href="#faq" class="hover:text-indigo-400 transition-colors">FAQ</a></li>
                        <li><a href="/login" class="hover:text-indigo-400 transition-colors">Admin Login</a></li>
                    </ul>
                </div>

                <!-- Col 3: Legal & Support -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Legal & Contact</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="/about" class="hover:text-indigo-400 transition-colors">About Us</a></li>
                        <li><a href="/privacy-policy" class="hover:text-indigo-400 transition-colors">Privacy Policy</a></li>
                        <li><a href="/terms-and-conditions" class="hover:text-indigo-400 transition-colors">Terms & Conditions</a></li>
                        <li><a href="/refund-policy" class="hover:text-indigo-400 transition-colors">Refund Policy</a></li>
                        <li><a href="/account-deletion" class="hover:text-indigo-400 transition-colors">Account Deletion</a></li>
                        <li><a href="/contact" class="hover:text-indigo-400 transition-colors">Contact Us</a></li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-slate-500">
                <p>&copy; 2026 EduvoraX. All rights reserved.</p>
                <p>School Management Software</p>
            </div>
        </div>
    </footer>

    <!-- Demo Request Modal -->
    <div id="demoModal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 md:p-8 shadow-2xl border border-slate-200 relative text-left space-y-6">
            <button onclick="closeDemoModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold text-sm">
                ✕
            </button>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Founding School Offer</span>
                <h3 class="text-2xl font-black text-slate-900 mt-1">Book a Free Demo</h3>
                <p class="text-xs text-slate-500 mt-1">Enter your school details to schedule a live demo and claim your 1st Year FREE offer.</p>
            </div>

            <form onsubmit="handleDemoSubmit(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">School Name *</label>
                    <input type="text" id="modalSchoolName" required placeholder="e.g. Saraswati Vidhyalay" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Your Name / Designation *</label>
                    <input type="text" id="modalPersonName" required placeholder="e.g. Principal / School Owner" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Phone / WhatsApp Number *</label>
                    <input type="tel" id="modalPhone" required placeholder="e.g. 9876543210" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-indigo-600">
                </div>
                <button type="submit" class="w-full py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider shadow-lg shadow-indigo-600/25 transition-all">
                    Submit Demo Request
                </button>
            </form>

            <div class="border-t border-slate-100 pt-4 text-center">
                <p class="text-[11px] text-slate-500 mb-2">Or chat instantly with our onboarding team:</p>
                <a href="https://wa.me/{{ $whatsappNumber }}?text=Hello%20EduvoraX%20Team%2C%20I%20would%20like%20to%20book%20a%20free%20demo%20for%20my%20school." target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:underline">
                    <span>💬 Open WhatsApp Directly →</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        const whatsappNumber = @json($whatsappNumber);
        function openDemoModal() {
            document.getElementById('demoModal').classList.remove('hidden');
        }
        function closeDemoModal() {
            document.getElementById('demoModal').classList.add('hidden');
        }
        function handleDemoSubmit(event) {
            event.preventDefault();
            const school = document.getElementById('modalSchoolName').value;
            const person = document.getElementById('modalPersonName').value;
            const phone = document.getElementById('modalPhone').value;
            
            const msg = encodeURIComponent(`Hello EduvoraX Team,\nI would like to book a free demo for my school.\n\nSchool Name: ${school}\nContact Person: ${person}\nPhone: ${phone}`);
            window.open(`https://wa.me/${whatsappNumber}?text=${msg}`, '_blank');
            closeDemoModal();
        }
    </script>

</body>
</html>
