<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="EduvoraX Privacy Policy - Learn how we protect and manage school data, student records, and user privacy.">
    <title>Privacy Policy — EduvoraX</title>

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
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Legal Document</span>
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 mt-2">Privacy Policy</h1>
                <p class="text-slate-500 text-sm mt-2">Last Updated: September 15, 2026</p>
            </div>

            <div class="prose prose-slate max-w-none space-y-6 text-sm md:text-base leading-relaxed text-slate-600">
                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">1. Introduction</h2>
                    <p>Welcome to <strong>EduvoraX</strong>. We respect the privacy of schools, administrators, teachers, students, and parents. This Privacy Policy outlines how we collect, use, protect, and handle data stored within the EduvoraX School Management Platform.</p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">2. Data We Collect</h2>
                    <p>EduvoraX operates as a multi-tenant institutional software provider. Information collected on behalf of participating schools includes:</p>
                    <ul class="list-disc pl-6 space-y-1">
                        <li><strong>School Information:</strong> School name, code, contact details, address, and official logos.</li>
                        <li><strong>Student & Parent Information:</strong> Admission numbers, student names, class/section assignments, attendance logs, exam marks, and parent contact details.</li>
                        <li><strong>Staff Information:</strong> Teacher names, employee IDs, department, designation, and login credentials.</li>
                        <li><strong>Financial Records:</strong> Fee structure configurations, receipt records, and transaction logs. Payment card details are handled directly by payment gateways (such as Razorpay) and are never stored on EduvoraX servers.</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">3. How We Use Data</h2>
                    <p>Data stored in EduvoraX is strictly used to deliver school administration services, including:</p>
                    <ul class="list-disc pl-6 space-y-1">
                        <li>Generating report cards, fee receipts, and attendance summaries.</li>
                        <li>Sending essential push notifications and SMS updates regarding homework, notices, and payment receipts.</li>
                        <li>Providing secure access control for school administrators, teachers, and parents.</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">4. Data Isolation & Security</h2>
                    <p>EduvoraX enforces strict multi-tenant isolation. Data belonging to one school cannot be accessed or viewed by another institution. We utilize encryption in transit (HTTPS/TLS) and secure database permissions to protect your records.</p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">5. Contact Us</h2>
                    <p>If you have questions regarding this Privacy Policy or data protection, please contact us at <a href="mailto:support@eduvorax.com" class="text-indigo-600 underline">support@eduvorax.com</a>.</p>
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
                <a href="/terms-and-conditions" class="hover:text-white">Terms & Conditions</a>
                <a href="/contact" class="hover:text-white">Contact Us</a>
            </div>
        </div>
    </footer>

</body>
</html>
