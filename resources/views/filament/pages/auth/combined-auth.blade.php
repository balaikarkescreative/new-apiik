<div
    x-data="{
        latitude: null,
        longitude: null,
        gpsStatus: 'idle',
        gpsError: null,
        showGpsGuideModal: false,
        browserTab: 'chrome',
        isSubmitting: false,

        init() {
            // Pre-fetch lokasi GPS diam-diam di latar belakang saat halaman pertama kali dibuka
            this.fetchLocationSilently();
        },

        fetchLocationSilently() {
            if (!navigator.geolocation) {
                this.gpsStatus = 'unsupported';
                this.gpsError = 'Peramban (browser) Anda tidak mendukung fitur lokasi GPS.';
                return;
            }

            this.gpsStatus = 'locating';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.latitude = position.coords.latitude;
                    this.longitude = position.coords.longitude;
                    this.gpsStatus = 'ready';
                    this.gpsError = null;

                    // Sinkronisasi koordinat ke Livewire di latar belakang
                    @this.set('latitude', this.latitude, false);
                    @this.set('longitude', this.longitude, false);
                    @this.set('isGps', true, false);

                    // Jika pengguna sedang menunggu tombol Masuk, lanjutkan login otomatis
                    if (this.isSubmitting) {
                        this.isSubmitting = false;
                        @this.authenticate();
                    }
                },
                (error) => {
                    this.gpsStatus = 'denied';
                    let msg = 'Izin akses lokasi (GPS) tidak diberikan.';
                    if (error.code === error.PERMISSION_DENIED) {
                        msg = 'Akses lokasi ditolak atau diblokir oleh peramban/perangkat Anda.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        msg = 'Informasi lokasi tidak tersedia saat ini. Pastikan GPS/Layanan Lokasi perangkat Anda telah diaktifkan.';
                    } else if (error.code === error.TIMEOUT) {
                        msg = 'Waktu permintaan lokasi habis (timeout). Silakan periksa koneksi dan coba lagi.';
                    }
                    this.gpsError = msg;

                    if (this.isSubmitting) {
                        this.isSubmitting = false;
                        this.showGpsGuideModal = true;
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        },

        submitWithGps() {
            if (this.isSubmitting) return;

            // 1. Jika koordinat GPS sudah siap: langsung submit instan (0 delay)!
            if (this.gpsStatus === 'ready' && this.latitude !== null && this.longitude !== null) {
                @this.set('latitude', this.latitude, false);
                @this.set('longitude', this.longitude, false);
                @this.set('isGps', true, false);
                @this.authenticate();
                return;
            }

            // 2. Jika GPS sebelumnya ditolak atau tidak didukung: tampilkan modal panduan
            if (this.gpsStatus === 'denied' || this.gpsStatus === 'unsupported') {
                this.showGpsGuideModal = true;
                return;
            }

            // 3. Jika GPS masih dalam proses locating: aktifkan loading dan tunggu
            this.isSubmitting = true;
            this.fetchLocationSilently();
        },

        retryGps() {
            this.showGpsGuideModal = false;
            this.isSubmitting = true;
            this.fetchLocationSilently();
        }
    }"
>
    {{-- Buttons Layout (outside info card) --}}
    <div class="flex flex-col sm:flex-row gap-3 w-full">
        {{-- Masuk Aplikasi Button --}}
        <button
            wire:click="openLoginModal"
            class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2 hover:shadow-xl hover:scale-[1.02]"
        >
            <x-filament::icon
                icon="heroicon-o-arrow-right-on-rectangle"
                class="w-5 h-5 flex-shrink-0"
            />
            <span>Masuk Aplikasi</span>
        </button>

        {{-- Pengajuan Akun Button --}}
        <button
            wire:click="checkOperationalHoursAndOpenRegister"
            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-all duration-200 flex items-center justify-center gap-2"
        >
            <x-filament::icon
                icon="heroicon-o-user-plus"
                class="w-5 h-5 flex-shrink-0"
            />
            <span>Pengajuan Akun</span>
        </button>

        {{-- Teleport modals to body --}}
        <template x-teleport="body">
            {{-- Login Modal --}}
            <x-filament::modal
                id="login-modal"
                width="md"
                :close-by-clicking-away="false"
            >
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon
                            icon="heroicon-o-arrow-right-on-rectangle"
                            class="h-6 w-6"
                        />
                        Masuk ke Aplikasi
                    </div>
                </x-slot>

                <x-slot name="description">
                    Silakan masukkan username/email dan password Anda
                </x-slot>

                <div
                    class="space-y-4"
                    @keydown.enter.prevent="submitWithGps()"
                >
                    <x-filament-panels::form @submit.prevent="submitWithGps()">
                        {{-- Login Form --}}
                        {{ $this->loginForm }}

                        <button
                            type="button"
                            @click="submitWithGps()"
                            :disabled="isSubmitting"
                            class="w-full mt-4 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <x-filament::icon
                                icon="heroicon-o-arrow-right-on-rectangle"
                                class="w-5 h-5 flex-shrink-0"
                            />
                            <span>Masuk</span>
                        </button>
                    </x-filament-panels::form>

                    {{-- Modal Panduan Izin GPS --}}
                    <div
                        x-show="showGpsGuideModal"
                        x-cloak
                        class="fixed inset-0 z-[99999] overflow-y-auto flex items-center justify-center p-4"
                        style="background-color: rgba(0, 0, 0, 0.65);"
                    >
                        <div
                            @click.away="showGpsGuideModal = false"
                            class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-lg w-full p-6 text-left border border-gray-100 dark:border-gray-800"
                        >
                            {{-- Header --}}
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950 flex items-center justify-center flex-shrink-0 text-rose-600 dark:text-rose-400">
                                    <x-filament::icon icon="heroicon-o-map-pin" class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        Akses Lokasi GPS Diperlukan
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Login dicegah karena perangkat/peramban tidak mengizinkan akses lokasi.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="showGpsGuideModal = false"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                                </button>
                            </div>

                            {{-- Alert Box --}}
                            <div class="mt-4 p-3 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800/50 rounded-xl text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                                <span x-text="gpsError"></span>
                            </div>

                            {{-- Tabs Browser --}}
                            <div class="mt-4">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Pilih peramban (browser) yang Anda gunakan untuk melihat panduan aktivasi:
                                </p>
                                <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs font-medium">
                                    <button
                                        type="button"
                                        @click="browserTab = 'chrome'"
                                        :class="browserTab === 'chrome' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                                        class="flex-1 py-1.5 px-2 rounded-md transition"
                                    >
                                        Chrome
                                    </button>
                                    <button
                                        type="button"
                                        @click="browserTab = 'edge'"
                                        :class="browserTab === 'edge' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                                        class="flex-1 py-1.5 px-2 rounded-md transition"
                                    >
                                        Edge
                                    </button>
                                    <button
                                        type="button"
                                        @click="browserTab = 'safari'"
                                        :class="browserTab === 'safari' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                                        class="flex-1 py-1.5 px-2 rounded-md transition"
                                    >
                                        Safari
                                    </button>
                                    <button
                                        type="button"
                                        @click="browserTab = 'firefox'"
                                        :class="browserTab === 'firefox' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                                        class="flex-1 py-1.5 px-2 rounded-md transition"
                                    >
                                        Firefox
                                    </button>
                                </div>

                                {{-- Tab Chrome --}}
                                <div x-show="browserTab === 'chrome'" class="mt-3 text-xs text-gray-600 dark:text-gray-300 space-y-2 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl">
                                    <p class="font-semibold text-gray-800 dark:text-gray-200">Di Komputer / Laptop (Chrome):</p>
                                    <ol class="list-decimal list-inside space-y-1 pl-1">
                                        <li>Klik ikon <strong>Setelan Situs / Gembok</strong> di sisi kiri kolom URL (alamat web).</li>
                                        <li>Cari menu izin <strong>Lokasi (Location)</strong>, lalu pilih <strong>Izinkan (Allow)</strong>.</li>
                                        <li>Klik tombol <strong>Coba Deteksi Ulang</strong> di bawah.</li>
                                    </ol>
                                    <p class="font-semibold text-gray-800 dark:text-gray-200 pt-1">Di Ponsel / HP Android:</p>
                                    <ol class="list-decimal list-inside space-y-1 pl-1">
                                        <li>Buka menu titik tiga (⋮) di Chrome > <strong>Setelan</strong> > <strong>Setelan Situs</strong> > <strong>Lokasi</strong>.</li>
                                        <li>Pastikan izin lokasi menyala dan situs ini tidak diblokir.</li>
                                        <li>Pastikan juga fitur <strong>GPS / Layanan Lokasi HP</strong> aktif.</li>
                                    </ol>
                                </div>

                                {{-- Tab Edge --}}
                                <div x-show="browserTab === 'edge'" class="mt-3 text-xs text-gray-600 dark:text-gray-300 space-y-2 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl">
                                    <ol class="list-decimal list-inside space-y-1 pl-1">
                                        <li>Klik ikon <strong>Gembok / Izin Situs</strong> di sisi kiri kolom URL.</li>
                                        <li>Pada opsi <strong>Izin untuk situs ini</strong>, ubah izin <strong>Lokasi</strong> menjadi <strong>Izinkan</strong>.</li>
                                        <li>Pastikan fitur Layanan Lokasi di Windows Anda aktif.</li>
                                        <li>Klik tombol <strong>Coba Deteksi Ulang</strong> di bawah.</li>
                                    </ol>
                                </div>

                                {{-- Tab Safari --}}
                                <div x-show="browserTab === 'safari'" class="mt-3 text-xs text-gray-600 dark:text-gray-300 space-y-2 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl">
                                    <p class="font-semibold text-gray-800 dark:text-gray-200">Di Mac (macOS):</p>
                                    <ol class="list-decimal list-inside space-y-1 pl-1">
                                        <li>Klik menu <strong>Safari</strong> di kiri atas > <strong>Pengaturan untuk Situs Web Ini...</strong></li>
                                        <li>Ubah pilihan <strong>Lokasi</strong> ke <strong>Izinkan</strong>.</li>
                                    </ol>
                                    <p class="font-semibold text-gray-800 dark:text-gray-200 pt-1">Di iPhone / iPad (iOS):</p>
                                    <ol class="list-decimal list-inside space-y-1 pl-1">
                                        <li>Buka <strong>Pengaturan HP</strong> > <strong>Privasi & Keamanan</strong> > <strong>Layanan Lokasi</strong> (Aktifkan).</li>
                                        <li>Pilih <strong>Situs Web Safari</strong> > pilih <strong>Saat Menggunakan App</strong>.</li>
                                    </ol>
                                </div>

                                {{-- Tab Firefox --}}
                                <div x-show="browserTab === 'firefox'" class="mt-3 text-xs text-gray-600 dark:text-gray-300 space-y-2 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl">
                                    <ol class="list-decimal list-inside space-y-1 pl-1">
                                        <li>Klik ikon <strong>Gembok / Info Izin</strong> di sebelah kiri kolom URL.</li>
                                        <li>Cari izin <strong>Akses Lokasi Anda</strong>, lalu klik tanda silang (x) untuk menghapus pemblokiran.</li>
                                        <li>Klik tombol <strong>Coba Deteksi Ulang</strong> di bawah, lalu klik <strong>Izinkan</strong> saat dialog konfirmasi muncul.</li>
                                    </ol>
                                </div>
                            </div>

                            {{-- Footer Buttons --}}
                            <div class="mt-5 flex gap-2 justify-end">
                                <button
                                    type="button"
                                    @click="showGpsGuideModal = false"
                                    class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition"
                                >
                                    Tutup
                                </button>
                                <button
                                    type="button"
                                    @click="retryGps()"
                                    class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg shadow transition flex items-center gap-1.5"
                                >
                                    <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4" />
                                    <span>Coba Deteksi Ulang Lokasi</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::modal>
        </template>

        <template x-teleport="body">

            {{-- Register Modal --}}
            <x-filament::modal
                id="register-modal"
                width="4xl"
                :close-by-clicking-away="false"
            >
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon
                            icon="heroicon-o-user-plus"
                            class="h-6 w-6"
                        />
                        Pengajuan Akun Baru
                    </div>
                </x-slot>

                <x-slot name="description">
                    Silakan isi formulir berikut untuk mengajukan akun baru. Pastikan semua informasi yang diberikan akurat dan lengkap.
                </x-slot>

                <div class="space-y-4" x-data @keydown.enter="$wire.register()">
                    <x-filament-panels::form wire:submit="register">
                        {{ $this->registerForm }}
                    </x-filament-panels::form>
                </div>

                <x-slot name="footerActions">
                    <x-filament::button
                        wire:click="register"
                        size="lg"
                        color="success"
                        icon="heroicon-o-check-circle"
                        class="w-full sm:w-auto"
                    >
                        Kirim Pengajuan
                    </x-filament::button>

                    <x-filament::button
                        wire:click="closeRegisterModal"
                        color="gray"
                        outlined
                    >
                        Batal
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>
        </template>
    </div>
</div>
