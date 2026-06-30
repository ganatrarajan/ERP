<template>
    <div class="min-h-screen flex items-center justify-center bg-radial from-slate-900 via-slate-950 to-black p-6">
        <div class="w-full max-w-md bg-slate-900/60 backdrop-blur-md border border-slate-800/80 p-8 rounded-2xl shadow-2xl">
            <!-- Header/Logo -->
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-3xl font-extrabold shadow-lg shadow-indigo-500/20 mb-3">
                    Ω
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-white">EduvoraX ERP</h2>
                <p class="text-sm text-slate-400 mt-1">Enterprise Management Portal</p>
            </div>
            <slot />
        </div>
    </div>
</template>

<script>
export default {
    name: 'AuthLayout'
}
</script>
