<div class="space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ $record->name }}</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->kotakab ?? 'Lokasi tidak diset' }} &bull; {{ $record->alamat ?? '-' }}</p>
        </div>
        <div>
            @if ($record->is_blocked)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                    <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                    Status Saat Ini: Diblokir
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                    <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                    Status Saat Ini: Aktif (Tidak Diblokir)
                </span>
            @endif
        </div>
    </div>

    @php
        $histories = $record->riwayatBlokir()->with('admin')->get();
    @endphp

    @if ($histories->isEmpty())
        <div class="text-center py-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 mb-3">
                <x-heroicon-o-shield-check class="w-6 h-6" />
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Belum ada riwayat blokir</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Faskes ini belum pernah memiliki catatan pemblokiran atau pembukaan blokir.</p>
        </div>
    @else
        <div class="flow-root max-h-[60vh] overflow-y-auto pr-1">
            <ul role="list" class="-mb-8">
                @foreach ($histories as $idx => $history)
                    <li>
                        <div class="relative pb-6">
                            @if (! $loop->last)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex items-start space-x-3">
                                <div>
                                    @if ($history->action === 'blokir')
                                        <span class="h-8 w-8 rounded-full bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 flex items-center justify-center ring-4 ring-white dark:ring-gray-900">
                                            <x-heroicon-m-lock-closed class="h-4 w-4" />
                                        </span>
                                    @else
                                        <span class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 flex items-center justify-center ring-4 ring-white dark:ring-gray-900">
                                            <x-heroicon-m-lock-open class="h-4 w-4" />
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-semibold">
                                            @if ($history->action === 'blokir')
                                                <span class="text-red-600 dark:text-red-400">Akun Diblokir</span>
                                            @else
                                                <span class="text-green-600 dark:text-green-400">Blokir Dibuka</span>
                                            @endif
                                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-2">
                                                oleh {{ $history->admin?->name ?? 'Admin / Sistem' }}
                                            </span>
                                        </div>
                                        <time class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                            {{ $history->created_at->format('d/m/Y H:i') }}
                                        </time>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/70 p-3 rounded-lg border border-gray-100 dark:border-gray-700/60 leading-relaxed whitespace-pre-wrap">
                                        {{ $history->alasan }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
