<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Warning Banner jika maintenance aktif -->
        @if($this->isMaintenanceActive)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400 dark:text-red-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                            ⚠️ Sistem dalam Mode Maintenance
                        </h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <p>Sistem sedang dalam maintenance mode. User tidak dapat mengakses aplikasi.</p>
                            @if($this->maintenanceStatus?->getBypassUrl())
                                <p class="mt-1">
                                    <strong>URL Bypass:</strong> 
                                    <code class="bg-red-100 dark:bg-red-800/50 px-1 rounded">{{ $this->maintenanceStatus->getBypassUrl() }}</code>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Success Banner jika maintenance tidak aktif -->
        @if(!$this->isMaintenanceActive)
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400 dark:text-green-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.107 10.5a.75.75 0 00-1.214 1.029l2.5 3.5a.75.75 0 001.214 0l4-5.6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                            ✅ Sistem Normal
                        </h3>
                        <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                            <p>Sistem sedang berjalan normal. User dapat mengakses aplikasi.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Form -->
        <div class="rounded-lg">
            {{ $this->form }}
        </div>

        <!-- Information Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-400 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-900 dark:text-blue-200">Secret Key</h3>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">Akses darurat untuk admin selama maintenance</p>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-400 dark:text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-900 dark:text-yellow-200">Retry After</h3>
                        <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">Browser menunggu sebelum coba lagi</p>
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800/50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-400 dark:text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-purple-900 dark:text-purple-200">Pesan User</h3>
                        <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">Komunikasi jelas dengan pengguna</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
