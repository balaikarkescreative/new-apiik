{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/components/layout/footer.blade.php --}}
<footer class="fi-footer w-full py-3 sm:py-4 text-center text-xs sm:text-sm transition-colors duration-200
 text-gray-700 dark:text-gray-300
 bg-white dark:bg-gray-900
 border-t border-gray-200/50 dark:border-gray-700/50">
    <div class="container mx-auto px-2 sm:px-4">
        <p class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 lg:gap-4">
            <span class="font-medium text-center">
                &copy; {{ date('Y') }} Balai Kekarantinaan Kesehatan Kelas I Bandung.
            </span>
            <span class="hidden sm:inline text-gray-400 dark:text-gray-600">|</span>
            <span class="flex items-center gap-1 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                <x-heroicon-o-users class="h-3 sm:h-4 w-3 sm:w-4 flex-shrink-0" />
                <span class="whitespace-nowrap text-xs sm:text-sm">
                    <span class="hidden sm:inline">Jumlah Pengunjung:</span>
                    <span class="sm:hidden">Pengunjung:</span>
                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($totalPengunjungLogin) }}</span>
                </span>
            </span>
        </p>
    </div>
</footer>
