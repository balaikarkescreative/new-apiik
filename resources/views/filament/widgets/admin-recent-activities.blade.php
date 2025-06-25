{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/widgets/admin-recent-activities.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Aktivitas Terbaru
        </x-slot>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Izin Operasional Terbaru -->
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3">Izin Klinik Terbaru</h4>
                <div class="space-y-3">
                    @forelse($this->getViewData()['recentIzin'] as $izin)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium text-sm">{{ $izin->user->name }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $izin->user->kotakab }} - {{ $izin->user->kategori ?? 'Klinik' }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $izin->tanggal_pengajuan->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">Tidak ada izin terbaru</p>
                    @endforelse
                </div>
            </div>

            <!-- Registrasi Klinik Terbaru -->
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3">Registrasi Klinik Terbaru</h4>
                <div class="space-y-3">
                    @forelse($this->getViewData()['recentRegistrations'] as $registration)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium text-sm">{{ $registration->name }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $registration->kotakab }} - {{ $registration->kategori ?? 'Klinik' }}
                                </p>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    {{ $registration->verifikasi_akun === 'Diterima' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                       ($registration->verifikasi_akun === 'Ditolak' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                    {{ $registration->verifikasi_akun ?? 'Belum Diverifikasi' }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $registration->created_at->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">Tidak ada registrasi terbaru</p>
                    @endforelse
                </div>
            </div>

            <!-- ICV Terbaru -->
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3">Pengajuan ICV Terbaru</h4>
                <div class="space-y-3">
                    @forelse($this->getViewData()['recentIcv'] as $icv)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium text-sm">{{ $icv->user->name }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    Jumlah: {{ $icv->jumlah_icv ?? 0 }} ICV - {{ $icv->status_icv ?? 'Menunggu' }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $icv->tanggal_pengajuan->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">Tidak ada pengajuan ICV terbaru</p>
                    @endforelse
                </div>
            </div>

            <!-- Laporan ICV Terbaru -->
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3">Laporan ICV Terbaru</h4>
                <div class="space-y-3">
                    @forelse($this->getViewData()['recentLaporan'] as $laporan)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium text-sm">{{ $laporan->user->name }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    ICV Terbit: {{ $laporan->jml_terbit_icv }} | Sisa: {{ $laporan->sisa_icv }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($laporan->tgl_pelaporan)->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">Tidak ada laporan terbaru</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
