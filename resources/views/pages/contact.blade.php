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
    <meta name="description" content="Contact EduvoraX - Get in touch with our team for free school demos, setup assistance, and support.">
    <title>Contact Us — EduvoraX</title>

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
            <div class="border-b border-slate-100 pb-6 text-center">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Get In Touch</span>
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 mt-2">Contact EduvoraX</h1>
                <p class="text-slate-500 text-sm mt-2 max-w-lg mx-auto">We are here to help school owners, principals, and administrators simplify their daily school management.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                <!-- Info Card 1 -->
                <div class="p-6 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-lg">💬</div>
                    <h3 class="font-bold text-slate-900 text-base">WhatsApp Support</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Connect with our onboarding team directly on WhatsApp.</p>
                    <a href="https://wa.me/{{ $whatsappNumber }}?text=Hello%20EduvoraX%20Team%2C%20I%20would%20like%20to%20book%20a%20free%20demo%20for%20my%20school." target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-emerald-600 hover:underline pt-2">
                        <span>Chat on WhatsApp →</span>
                    </a>
                </div>

                <!-- Info Card 2 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/60 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg">📞</div>
                    <h3 class="font-bold text-slate-900 text-base">Phone Number</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Call our administrative line for inquiries and demo booking.</p>
                    <a href="tel:{{ $contactPhone }}" class="inline-block text-xs font-bold text-slate-900 hover:underline pt-2">
                        {{ $contactPhone }}
                    </a>
                </div>

                <!-- Info Card 3 -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/60 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-lg">✉️</div>
                    <h3 class="font-bold text-slate-900 text-base">Email Support</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Send an email for system integration or onboarding support.</p>
                    <a href="mailto:{{ $contactEmail }}" class="inline-block text-xs font-bold text-indigo-600 hover:underline pt-2">
                        {{ $contactEmail }}
                    </a>
                </div>
            </div>

            <!-- Founding School Notice -->
            <div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-900 to-slate-900 text-white space-y-2 text-center">
                <span class="inline-block px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-extrabold uppercase tracking-wider">Founding School Program</span>
                <h4 class="text-lg font-bold">First Year FREE for Founding Schools</h4>
                <p class="text-xs text-slate-300 max-w-md mx-auto">{{ $foundingOfferText }}</p>
                <div class="pt-2">
                    <a href="/#demo" class="inline-block px-6 py-2.5 rounded-full bg-indigo-600 text-white text-xs font-bold uppercase tracking-wider hover:bg-indigo-500 transition-colors">
                        Book Free Demo
                    </a>
                </div>
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
                <a href="/terms-and-conditions" class="hover:text-white">Terms & Conditions</a>
            </div>
        </div>
    </footer>

</body>
</html>
