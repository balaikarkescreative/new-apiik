{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/widgets/user-status.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Status & Aktivitas Saya
        </x-slot>

        <div class="space-y-6">
            <!-- Status Akun -->
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3">Status Akun</h4>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium">{{ $this->getViewData()['userStatus']->name }}</span>
                        <div class="flex gap-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $this->getViewData()['userStatus']->verifikasi_akun === 'Diterima' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                   ($this->getViewData()['userStatus']->verifikasi_akun === 'Ditolak' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                {{ $this->getViewData()['userStatus']->verifikasi_akun ?? 'Belum Diverifikasi' }}
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $this->getViewData()['userStatus']->status_akun === 'Aktif' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                                {{ $this->getViewData()['userStatus']->status_akun ?? 'Tidak Aktif' }}
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $this->getViewData()['userStatus']->kotakab }} - {{ $this->getViewData()['userStatus']->kategori ?? 'Klinik' }}
                    </p>
                </div>
            </div>

            <!-- Status Izin Terakhir -->
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3">Status Izin Operasional Terakhir</h4>
                @if($this->getViewData()['latestIzin'])
                    @php $izin = $this->getViewData()['latestIzin']; @endphp
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium">Diajukan: {{ $izin->tanggal_pengajuan->format('d F Y') }}</span>
                            @if($izin->izinPersetujuan)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Disetujui
                                </span>
                            @elseif($izin->izinDokumen)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    {{ match($izin->izinDokumen->hasil) {
                                        1 => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        2 => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        default => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                    } }}">
                                    {{ match($izin->izinDokumen->hasil) {
                                        1 => 'Berkas Lengkap',
                                        2 => 'Berkas Tidak Lengkap',
                                        default => 'Belum Diverifikasi'
                                    } }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    Menunggu Verifikasi
                                </span>
                            @endif
                        </div>

                        @if($izin->izinDokumen && $izin->izinDokumen->catatan)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <strong>Catatan:</strong> {{ $izin->izinDokumen->catatan }}
                            </p>
                        @endif
                    </div>
                @else
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center text-gray-500 dark:text-gray-400 text-sm">
                        Belum ada izin yang diajukan
                    </div>
                @endif
            </div>

            <!-- Status ICV Terakhir -->
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3">Status ICV Terakhir</h4>
                @if($this->getViewData()['latestIcv'])
                    @php $icv = $this->getViewData()['latestIcv']; @endphp
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium">Diajukan: {{ $icv->tanggal_pengajuan->format('d F Y') }}</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $icv->status_icv === 'Aktif' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                {{ $icv->status_icv ?? 'Menunggu Proses' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Jumlah ICV: {{ $icv->jumlah_icv ?? 0 }}
                        </p>
                    </div>
                @else
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center text-gray-500 dark:text-gray-400 text-sm">
                        Belum ada ICV yang diajukan
                    </div>
                @endif
            </div>

            <!-- Laporan ICV Terbaru -->
            <div>
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3">Laporan ICV Terbaru</h4>
                <div class="space-y-2">
                    @forelse($this->getViewData()['latestLaporan'] as $laporan)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="text-sm font-medium">{{ \Carbon\Carbon::parse($laporan->tgl_pelaporan)->format('d F Y') }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    ICV Terbit: {{ $laporan->jml_terbit_icv }} | Sisa: {{ $laporan->sisa_icv }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($laporan->tgl_pelaporan)->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center text-gray-500 dark:text-gray-400 text-sm">
                            Belum ada laporan ICV
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
