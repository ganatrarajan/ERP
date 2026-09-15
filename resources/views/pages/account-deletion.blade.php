<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="EduvoraX Account & Data Deletion Policy for mobile app users, students, and institutions.">
    <title>Account Deletion Policy — EduvoraX</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
    <style> body { font-family: 'Outfit', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-black text-sm tracking-wider">
                    EVX
                </div>
                <span class="text-2xl font-extrabold text-slate-900">EduvoraX</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="/" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 transition-colors">Back to Home</a>
                <a href="/login" class="px-5 py-2.5 rounded-full bg-indigo-600 text-white text-xs font-bold uppercase tracking-wider hover:bg-indigo-700 transition-colors">
                    Sign In
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-16 flex-1">
        <div class="bg-white rounded-3xl p-8 md:p-12 border border-slate-200 shadow-sm space-y-8">
            <div class="border-b border-slate-100 pb-6">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Data Management</span>
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 mt-2">Account & Data Deletion Request</h1>
                <p class="text-slate-500 text-sm mt-2">Last Updated: September 15, 2026</p>
            </div>

            <div class="prose prose-slate max-w-none space-y-6 text-sm md:text-base leading-relaxed text-slate-600">
                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">1. Overview</h2>
                    <p>EduvoraX provides mobile app and web access to students, parents, teachers, and school administrators. This policy explains how users can request account deactivation or data deletion under Google Play and App Store guidelines.</p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">2. Student & Parent Mobile Accounts</h2>
                    <p>Student and Parent mobile app credentials are managed directly by your school administration. To request account deletion or data removal:</p>
                    <ol class="list-decimal pl-6 space-y-2">
                        <li>Contact your school administration or principal's office with your admission number and student details.</li>
                        <li>The school admin can deactivate or mark the student profile as deleted in the EduvoraX Admin Console.</li>
                        <li>Alternatively, send an email request to <a href="mailto:support@eduvorax.com" class="text-indigo-600 underline">support@eduvorax.com</a> with your registered school code and admission number.</li>
                    </ol>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">3. Data Retention Notice</h2>
                    <p>Certain historical academic, attendance, and fee ledger records may be retained by the school for statutory legal compliance and institutional archiving as required by education department regulations.</p>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 text-white py-8 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-400">
            <p>&copy; 2026 EduvoraX. All rights reserved.</p>
            <div class="flex gap-6">
                <a href="/" class="hover:text-white">Home</a>
                <a href="/privacy-policy" class="hover:text-white">Privacy Policy</a>
                <a href="/contact" class="hover:text-white">Contact Us</a>
            </div>
        </div>
    </footer>

</body>
</html>
