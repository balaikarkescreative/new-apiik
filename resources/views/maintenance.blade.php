<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Sedang Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Dark mode detection
        tailwind.config = {
            darkMode: 'media', // Gunakan system preference
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full">
        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-2xl border-0 dark:border dark:border-gray-700 p-8 text-center">
            <!-- Icon -->
            <div class="mx-auto w-20 h-20 bg-gradient-to-r from-blue-500 to-indigo-600 dark:from-blue-400 dark:to-indigo-500 rounded-full flex items-center justify-center mb-6 shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>

            <!-- Title -->
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                Sistem Sedang Maintenance
            </h1>

            <!-- Message -->
            <div class="text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                @php
                    // Baca data maintenance dari file cache karena database tidak tersedia saat maintenance
                    $maintenanceFile = storage_path('framework/maintenance.json');
                    $maintenance = null;
                    $message = null;
                    $retrySeconds = 60;
                    
                    if (file_exists($maintenanceFile)) {
                        $maintenanceData = json_decode(file_get_contents($maintenanceFile), true);
                        if ($maintenanceData) {
                            $message = $maintenanceData['message'] ?? null;
                            $retrySeconds = $maintenanceData['retry_after'] ?? 60;
                        }
                    }
                @endphp
                
                @if($message)
                    {!! nl2br(e($message)) !!}
                @else
                    <p>Kami sedang melakukan pemeliharaan sistem untuk meningkatkan kualitas layanan.</p>
                    <p class="mt-2">Mohon maaf atas ketidaknyamanan ini.</p>
                @endif
            </div>

            <!-- Retry Info -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-center text-blue-800 dark:text-blue-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium">
                        Silakan coba lagi dalam {{ $retrySeconds }} detik
                    </span>
                </div>
            </div>

            <!-- Auto Refresh Timer -->
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Halaman akan refresh otomatis dalam: <span id="countdown" class="font-semibold text-indigo-600 dark:text-indigo-300">--</span>
            </div>

            <!-- Manual Refresh Button -->
            <button onclick="window.location.reload()" class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-600 dark:hover:from-blue-600 dark:hover:to-indigo-700 text-white font-medium py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 shadow-lg hover:shadow-xl">
                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Coba Lagi
            </button>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-sm text-gray-500 dark:text-gray-400">
            <p>Terima kasih atas kesabaran Anda</p>
        </div>
    </div>

    <!-- Auto Refresh Script -->
    <script>
        const retryAfter = {{ $retrySeconds ?? 60 }};
        let timeLeft = retryAfter;
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const display = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
            document.getElementById('countdown').textContent = display;
            
            if (timeLeft <= 0) {
                window.location.reload();
                return;
            }
            
            timeLeft--;
        }
        
        // Update every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
