<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A comprehensive, multi-tenant School ERP System offering complete student management, fee collection, attendance tracking, and examination management.">
    <meta name="keywords" content="School ERP, Student Management, Fee Management, Attendance, School Software">
    
    <title>EduvoraX ERP | Premium Institutional Management System</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Outfit', sans-serif; }
        
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(241, 245, 249, 0.9);
        }
        
        .hero-gradient {
            background: radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.15), transparent 50%),
                        radial-gradient(circle at 20% 80%, rgba(14, 165, 233, 0.15), transparent 50%);
        }
        
        .text-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #0ea5e9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .cta-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        }
        
        .feature-card {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 30px 60px -15px rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.25);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 6s infinite ease-in-out;
        }

        @keyframes pulse-slow {
            0%, 100% { transform: scale(1) translate(0px, 0px); }
            50% { transform: scale(1.08) translate(10px, -10px); }
        }
        .animate-blob-slow {
            animation: pulse-slow 10s infinite ease-in-out;
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
                
                <div class="hidden md:flex items-center space-x-10 font-bold text-xs uppercase tracking-wider text-slate-500">
                    <a href="#features" class="hover:text-indigo-600 transition-colors">ERP Modules</a>
                    <a href="#gateways" class="hover:text-indigo-600 transition-colors">Direct Settlements</a>
                    <a href="#notifications" class="hover:text-indigo-600 transition-colors">FCM Engine</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="/login" class="text-xs font-bold uppercase tracking-wider text-slate-700 hover:text-indigo-600 transition-colors">Sign In</a>
                    <a href="/login" class="px-5 py-2.5 rounded-full bg-slate-900 text-white text-xs font-bold uppercase tracking-wider hover:bg-indigo-600 hover:shadow-lg hover:shadow-indigo-500/25 transition-all duration-300 transform hover:-translate-y-0.5">
                        Access Console
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-44 lg:pb-28 hero-gradient overflow-hidden">
        <!-- Decorative blobs -->
        <div class="absolute top-20 right-10 w-96 h-96 bg-indigo-300/10 rounded-full mix-blend-multiply filter blur-3xl animate-blob-slow"></div>
        <div class="absolute top-40 -left-10 w-96 h-96 bg-sky-300/10 rounded-full mix-blend-multiply filter blur-3xl animate-blob-slow" style="animation-delay: 4s;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-750 text-xs font-bold uppercase tracking-wider mb-8 shadow-sm">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                Direct Bank-to-Bank School Settlements
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black text-slate-900 tracking-tight leading-[1.08] mb-6">
                Premium Institutional<br/>
                <span class="text-gradient">ERP Platform</span>
            </h1>
            
            <p class="mt-6 text-lg md:text-xl text-slate-500 max-w-3xl mx-auto font-medium leading-relaxed mb-10">
                A unified multi-tenant platform for modern institutions. Secure multi-school billing integrations, student management registries, attendance engines, and real-time push synchronization.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/login" class="px-8 py-4 w-full sm:w-auto rounded-full bg-gradient-to-r from-indigo-600 to-indigo-500 text-white text-xs font-bold uppercase tracking-wider shadow-xl shadow-indigo-500/25 hover:shadow-indigo-500/35 hover:scale-105 transition-all duration-300">
                    Access Console
                </a>
                <a href="#features" class="px-8 py-4 w-full sm:w-auto rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-bold uppercase tracking-wider shadow-sm hover:border-indigo-200 hover:text-indigo-600 transition-all duration-300">
                    Explore Features
                </a>
            </div>

            <!-- Dashboard Preview Interactive Mockup -->
            <div class="mt-16 relative mx-auto max-w-5xl animate-float">
                <div class="rounded-3xl border border-slate-200/60 bg-white/50 backdrop-blur-xl p-3 shadow-2xl overflow-hidden relative">
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-inner flex flex-col">
                        <!-- Mock Header -->
                        <div class="h-12 border-b border-slate-150 bg-slate-50/50 flex items-center px-4 justify-between">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-rose-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            </div>
                            <div class="text-xs font-bold text-slate-400">EduvoraX Web Admin Console</div>
                            <div class="w-12"></div>
                        </div>
                        
                        <!-- Dashboard CSS Mockup Content -->
                        <div class="bg-slate-900 text-slate-100 p-6 flex flex-col md:flex-row gap-6 min-h-[420px] text-left">
                            <!-- Sidebar -->
                            <div class="w-full md:w-48 shrink-0 flex flex-col justify-between border-r border-slate-800/80 pr-4 hidden md:flex">
                                <div class="space-y-6">
                                    <div class="flex items-center gap-2 px-2">
                                        <span class="text-[9px] font-extrabold tracking-wider uppercase text-slate-500">Navigation</span>
                                    </div>
                                    <nav class="space-y-1">
                                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold bg-indigo-600/10 text-indigo-400 border border-indigo-500/20">
                                            📊 Overview
                                        </a>
                                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-slate-400 hover:bg-slate-850 hover:text-slate-200 transition-all">
                                            👥 Students
                                        </a>
                                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-slate-400 hover:bg-slate-850 hover:text-slate-200 transition-all">
                                            💳 Fee Collections
                                        </a>
                                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-slate-400 hover:bg-slate-850 hover:text-slate-200 transition-all">
                                            ⚡ Payment Gateways
                                        </a>
                                    </nav>
                                </div>
                                <div class="p-2.5 bg-slate-850 rounded-xl border border-slate-800 text-[10px] text-slate-400">
                                    System Mode: <strong class="text-indigo-400">Multi-Tenant</strong>
                                </div>
                            </div>

                            <!-- Dashboard Content -->
                            <div class="flex-1 space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <!-- Stat 1 -->
                                    <div class="bg-slate-850 border border-slate-800 p-4 rounded-xl flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-wider">Fees Collected</p>
                                            <h4 class="text-lg font-black text-white mt-1">₹4,82,500</h4>
                                        </div>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">+12%</span>
                                    </div>
                                    <!-- Stat 2 -->
                                    <div class="bg-slate-850 border border-slate-800 p-4 rounded-xl flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-wider">Online Revenue</p>
                                            <h4 class="text-lg font-black text-indigo-400 mt-1">₹2,84,200</h4>
                                        </div>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">Direct</span>
                                    </div>
                                    <!-- Stat 3 -->
                                    <div class="bg-slate-850 border border-slate-800 p-4 rounded-xl flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-wider">Attendance Rate</p>
                                            <h4 class="text-lg font-black text-white mt-1">96.8%</h4>
                                        </div>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700">Healthy</span>
                                    </div>
                                </div>

                                <!-- Lower Layout Grid -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Collection chart placeholder -->
                                    <div class="bg-slate-850 border border-slate-800 p-4 rounded-xl space-y-3">
                                        <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                            Consolidated Revenue Ledger
                                        </h5>
                                        <div class="h-32 flex items-end justify-between gap-1.5 pt-4 relative">
                                            <div class="absolute inset-x-0 top-0 border-t border-slate-800/40"></div>
                                            <div class="absolute inset-x-0 top-1/2 border-t border-slate-800/40"></div>
                                            <!-- Bars -->
                                            <div class="w-full bg-slate-800 rounded-t h-1/3 flex flex-col justify-end overflow-hidden"><div class="bg-indigo-650/60 h-1/2"></div></div>
                                            <div class="w-full bg-slate-800 rounded-t h-1/2 flex flex-col justify-end overflow-hidden"><div class="bg-indigo-600/60 h-2/3"></div></div>
                                            <div class="w-full bg-slate-800 rounded-t h-2/3 flex flex-col justify-end overflow-hidden"><div class="bg-indigo-550 h-3/4"></div></div>
                                            <div class="w-full bg-slate-800 rounded-t h-5/6 flex flex-col justify-end overflow-hidden"><div class="bg-gradient-to-t from-indigo-600 to-indigo-400 h-full"></div></div>
                                            <div class="w-full bg-slate-800 rounded-t h-3/4 flex flex-col justify-end overflow-hidden"><div class="bg-indigo-550 h-2/3"></div></div>
                                        </div>
                                        <div class="flex justify-between text-[8px] font-bold text-slate-500 uppercase tracking-wider px-1">
                                            <span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span>
                                        </div>
                                    </div>

                                    <!-- Recent Payments -->
                                    <div class="bg-slate-850 border border-slate-800 p-4 rounded-xl space-y-3">
                                        <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Recent Online Collections
                                        </h5>
                                        <div class="space-y-2">
                                            <div class="bg-slate-900/60 border border-slate-800/80 rounded-lg p-2.5 flex items-center justify-between text-[11px]">
                                                <div>
                                                    <span class="font-bold text-slate-200">Rahul Sharma (Class 8-A)</span>
                                                    <span class="text-[9px] text-slate-500 block mt-0.5">Term 1 Fees</span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="font-bold text-white">₹4,500</span>
                                                    <span class="text-[8px] text-emerald-450 block font-bold mt-0.5">● Received</span>
                                                </div>
                                            </div>
                                            <div class="bg-slate-900/60 border border-slate-800/80 rounded-lg p-2.5 flex items-center justify-between text-[11px]">
                                                <div>
                                                    <span class="font-bold text-slate-200">Priya Patel (Class 5-C)</span>
                                                    <span class="text-[9px] text-slate-500 block mt-0.5">Term 2 Fees</span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="font-bold text-white">₹6,000</span>
                                                    <span class="text-[8px] text-emerald-450 block font-bold mt-0.5">● Received</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Statistics -->
    <section class="py-12 bg-white border-y border-slate-200/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-indigo-600 tracking-tight">100%</div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Direct Settlement</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-indigo-600 tracking-tight">&lt; 1s</div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Notification Latency</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-indigo-600 tracking-tight">Multi</div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">School Isolation</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-indigo-600 tracking-tight">PDF</div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Receipt Rendering</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Multi-Tenant Gateway Settlement Section -->
    <section id="gateways" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-5 space-y-6">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold uppercase tracking-wider">
                        SaaS Financial Isolation
                    </div>
                    <h2 class="text-3xl md:text-5xl font-black tracking-tight leading-tight text-slate-900">
                        Direct Account <br/>
                        <span class="text-gradient">Settlement Architecture</span>
                    </h2>
                    <p class="text-slate-500 leading-relaxed font-medium">
                        EduvoraX ERP delegates all online gateway configuration directly to individual schools. Parents pay online, and deposits clear straight into the respective school's bank account. EduvoraX serves strictly as the platform management agent.
                    </p>
                    <div class="p-4 bg-indigo-50 border border-indigo-100/50 rounded-2xl text-xs font-semibold text-indigo-750 flex items-start gap-2.5">
                        <span class="text-base mt-0.5">🔒</span>
                        <span>All Key Secrets and Webhook tokens are protected by transparent AES-256 database row-level encryption.</span>
                    </div>
                </div>
                
                <!-- Comparative diagrams -->
                <div class="lg:col-span-7 grid grid-cols-1 md:grid-cols-2 gap-6 text-left">
                    <!-- School Account Card 1 -->
                    <div class="bg-slate-50 border border-slate-200/60 p-6 rounded-2xl flex flex-col justify-between shadow-sm relative">
                        <div class="absolute top-4 right-4 text-[10px] font-black uppercase tracking-wider text-slate-400 bg-slate-200/50 px-2 py-0.5 rounded">Active</div>
                        <div class="space-y-4">
                            <span class="text-2xl">🏫</span>
                            <h4 class="font-bold text-slate-900 text-base">Oakwood International</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Configured with individual merchant Razorpay keys. Fees paid by Oakwood students bypass middle servers completely.</p>
                            <div class="pt-2 border-t border-slate-200 text-[10px] font-semibold text-slate-500 space-y-1">
                                <div>Currency: <strong class="text-slate-700">INR</strong></div>
                                <div>Status: <strong class="text-emerald-600">Verified & Live</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- School Account Card 2 -->
                    <div class="bg-slate-50 border border-slate-200/60 p-6 rounded-2xl flex flex-col justify-between shadow-sm relative">
                        <div class="absolute top-4 right-4 text-[10px] font-black uppercase tracking-wider text-slate-450 bg-slate-200/50 px-2 py-0.5 rounded">Active</div>
                        <div class="space-y-4">
                            <span class="text-2xl">🏛️</span>
                            <h4 class="font-bold text-slate-900 text-base">Beacon Academy</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Direct gateway parameters allow independent ledger controls and distinct currencies per tenant site.</p>
                            <div class="pt-2 border-t border-slate-200 text-[10px] font-semibold text-slate-500 space-y-1">
                                <div>Currency: <strong class="text-slate-700">USD</strong></div>
                                <div>Status: <strong class="text-emerald-600">Verified & Live</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Real-time FCM Notification Section -->
    <section id="notifications" class="py-24 bg-slate-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-indigo-500/5 mix-blend-color-burn"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="space-y-6 text-left">
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/25 text-indigo-400 text-xs font-bold uppercase tracking-wider">
                        Real-time Synchronization
                    </div>
                    <h2 class="text-3xl md:text-5xl font-black tracking-tight leading-tight">
                        State-of-the-Art <br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-sky-400">FCM Notification Center</span>
                    </h2>
                    <p class="text-slate-400 text-lg leading-relaxed">
                        Integrated with Firebase JWT protocols to push updates to student devices instantly, automatically syncing local caching registers.
                    </p>
                    
                    <ul class="space-y-4 text-xs font-semibold text-slate-350">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 mt-0.5">✓</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">Class & Section Smart Grouping</h4>
                                <p class="text-[11px] text-slate-450 mt-1">Homework, notice boards, or syllabus logs sync dynamically according to section mappings.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 mt-0.5">✓</span>
                            <div>
                                <h4 class="font-bold text-white text-sm">Personalized Instant Billing Hooks</h4>
                                <p class="text-[11px] text-slate-450 mt-1">Automatic notification dispatch upon installment billing, receipts creation, or marks posting.</p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="relative bg-slate-800/40 border border-slate-700/50 rounded-3xl p-8 backdrop-blur-xl">
                    <div class="absolute -top-6 -right-6 w-32 h-32 bg-sky-500/10 rounded-full blur-2xl"></div>
                    
                    <h3 class="text-xs font-bold uppercase tracking-wider mb-6 text-indigo-400 flex items-center gap-2 text-left">
                        <span class="flex h-2 w-2 rounded-full bg-indigo-400"></span>
                        Simulated Student App Pushes
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Mock Push 1 -->
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-4 transform hover:scale-[1.02] transition-transform text-left">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600/20 flex items-center justify-center text-indigo-450 shrink-0 text-base">
                                📚
                            </div>
                            <div>
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-xs text-white">New Homework Assigned</h5>
                                    <span class="text-[9px] text-slate-500">Just Now</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Physics: Mechanics assignments. Submission Date: 2026-07-15.</p>
                                <span class="inline-block px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20 text-[9px] font-bold text-indigo-400 mt-2">Deep link: HomeworkScreen</span>
                            </div>
                        </div>

                        <!-- Mock Push 2 -->
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex gap-4 transform hover:scale-[1.02] transition-transform text-left">
                            <div class="w-10 h-10 rounded-xl bg-emerald-600/20 flex items-center justify-center text-emerald-455 shrink-0 text-base">
                                💵
                            </div>
                            <div>
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-xs text-white">Fee Receipt Generated</h5>
                                    <span class="text-[9px] text-slate-500">2m ago</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Receipt REC-1-000142 created for ₹5,000 Term 1 collection.</p>
                                <span class="inline-block px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-[9px] font-bold text-emerald-400 mt-2">Deep link: FeesScreen</span>
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 text-left">
                <!-- Core Module: Student Info -->
                <div class="feature-card bg-slate-50 border border-slate-200/60 p-8 rounded-2xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-6 font-bold text-lg">01</div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Student Information</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-4">Detailed digital profile logs. Track admissions, parental associations, academic history, file attachments, and active enrollment status.</p>
                    </div>
                    <div class="text-[9px] font-bold text-indigo-600 uppercase tracking-widest mt-4">Admissions & Demographic Logs</div>
                </div>

                <!-- Core Module: Finance -->
                <div class="feature-card bg-slate-50 border border-slate-200/60 p-8 rounded-2xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-6 font-bold text-lg">02</div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Fee Management</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-4">Structured fee templates, custom discounts, automatic dynamic late fine calculations, collections records, and PDF receipt rendering.</p>
                    </div>
                    <div class="text-[9px] font-bold text-emerald-600 uppercase tracking-widest mt-4">Invoicing & Collections</div>
                </div>

                <!-- Core Module: Attendance -->
                <div class="feature-card bg-slate-50 border border-slate-200/60 p-8 rounded-2xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center mb-6 font-bold text-lg">03</div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Attendance Engine</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-4">Record student and staff daily logs. Integrated holiday exception logic and detailed analytics for identifying absenteeism patterns.</p>
                    </div>
                    <div class="text-[9px] font-bold text-sky-600 uppercase tracking-widest mt-4">Student & Staff Rosters</div>
                </div>

                <!-- Core Module: Examinations -->
                <div class="feature-card bg-slate-50 border border-slate-200/60 p-8 rounded-2xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center mb-6 font-bold text-lg">04</div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Examinations & Marks</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-4">Exam schedules, syllabus assignments, raw marks entry, custom grade scale mapping, and auto-generated term report cards.</p>
                    </div>
                    <div class="text-[9px] font-bold text-rose-600 uppercase tracking-widest mt-4">Schedules, Marks & Grades</div>
                </div>

                <!-- Core Module: Homework -->
                <div class="feature-card bg-slate-50 border border-slate-200/60 p-8 rounded-2xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-6 font-bold text-lg">05</div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Homework & Notice Boards</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-4">Syllabus assignment tracking, document attachments storage, and target notices dispatched class-wise, section-wise, or school-wide.</p>
                    </div>
                    <div class="text-[9px] font-bold text-amber-600 uppercase tracking-widest mt-4">Homeworks & Bulletins</div>
                </div>

                <!-- Core Module: Multi-tenant -->
                <div class="feature-card bg-slate-50 border border-slate-200/60 p-8 rounded-2xl flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mb-6 font-bold text-lg">06</div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">SaaS Isolation Architecture</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-4">Enforce absolute data boundaries. School isolation on all tables, custom roles with spatie-permissions, and central super administrator tools.</p>
                    </div>
                    <div class="text-[9px] font-bold text-violet-600 uppercase tracking-widest mt-4">Multi-Tenant Scoping</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-24 relative overflow-hidden cta-gradient text-white">
        <div class="absolute inset-0 bg-indigo-600/5 mix-blend-multiply"></div>
        <div class="max-w-4xl mx-auto px-4 relative z-10 text-center space-y-6">
            <h2 class="text-4xl font-black tracking-tight">Ready to modernise your operations?</h2>
            <p class="text-slate-400 text-base max-w-xl mx-auto">Get started with our system to reduce administrative workloads and keep your entire school connected instantly.</p>
            <a href="/login" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-indigo-600 hover:bg-indigo-550 text-white text-xs font-bold uppercase tracking-wider hover:scale-105 transition-all duration-300 shadow-xl shadow-indigo-600/20">
                Log into Admin Console
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
                <span class="text-xl font-bold text-white tracking-tight">EduvoraX ERP</span>
            </div>
            <p class="text-slate-500 text-sm font-medium">
                &copy; {{ date('Y') }} Institutional Management Systems. All rights reserved.
            </p>
            <div class="flex gap-6 text-sm text-slate-500">
                <a href="#" class="hover:text-indigo-400 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-indigo-400 transition-colors">Terms of Service</a>
            </div>
        </div>
    </footer>

</body>
</html>
