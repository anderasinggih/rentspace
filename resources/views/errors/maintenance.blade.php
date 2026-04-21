<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Dalam Pemeliharaan - Rent Space</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-red-600 dark:bg-red-950 flex items-center justify-center min-h-screen p-6 transition-colors duration-300">
    <div class="max-w-md w-full text-center space-y-8">
        <!-- Illustration / Icon -->
        <div class="relative inline-block">
            <div class="absolute inset-0 bg-white blur-3xl opacity-20 rounded-full"></div>
            <div class="relative bg-red-500 dark:bg-red-900 p-8 rounded-3xl shadow-2xl border border-red-400 dark:border-red-800">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                    <path d="m8 14-2.5 2.5" />
                    <path d="m9 13 2 2" />
                </svg>
            </div>
        </div>

        <div class="space-y-4">
            <h1 class="text-3xl font-bold text-white tracking-tight">Sistem Dalam Pemeliharaan</h1>
            <p class="text-red-50 dark:text-red-100 leading-relaxed opacity-90">
                {{ $message }}
            </p>
        </div>

        <!-- Contact / Info -->
        <div class="pt-8 border-t border-red-500 dark:border-red-800">
            <p class="text-xs font-semibold text-red-200 dark:text-red-400 uppercase tracking-widest mb-4">Butuh bantuan mendesak?</p>
            <div class="flex justify-center gap-4">
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', \App\Models\Setting::getVal('admin_wa', '')) }}" 
                   class="inline-flex items-center gap-2 bg-white px-6 py-3 rounded-full shadow-lg text-sm font-bold text-red-600 hover:bg-red-50 transition-all active:scale-95">
                   <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                   Hubungi Admin via WhatsApp
                </a>
            </div>
        </div>

        <p class="text-sm text-red-300 dark:text-red-500 italic">
            &copy; {{ date('Y') }} Rent Space. Terimakasih atas kesabaran Anda.
        </p>
    </div>
</body>
</html>
