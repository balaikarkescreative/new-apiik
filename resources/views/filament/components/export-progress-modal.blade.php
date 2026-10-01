<div
    x-data="exportProgressModal({
        csrfToken: '{{ csrf_token() }}',
        processUrl: '{{ route('export.process-chunk') }}',
        finalizeUrl: '{{ route('export.finalize') }}',
        cancelUrl: '{{ route('export.cancel') }}'
    })"
    x-show="isOpen"
    x-cloak
    @open-export-progress-modal.window="startExport($event.detail)"
    class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto bg-gray-950/70 p-4 backdrop-blur-sm transition-opacity"
    style="display: none;"
>
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="if (isFinished || isError) close()"
        class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-2xl transition-all dark:bg-gray-900 border border-gray-200 dark:border-gray-800"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400">
                    <template x-if="!isFinished && !isError">
                        <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="isFinished">
                        <svg class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="isError">
                        <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </template>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="subtitle"></p>
                </div>
            </div>
            <span
                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium uppercase tracking-wider ring-1 ring-inset"
                :class="{
                    'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-950/50 dark:text-primary-400 dark:ring-primary-400/20': !isFinished && !isError,
                    'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400': isFinished,
                    'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/50 dark:text-red-400': isError
                }"
                x-text="format.toUpperCase()"
            ></span>
        </div>

        {{-- Progress Content --}}
        <div class="my-5">
            <div class="mb-2 flex items-center justify-between text-xs">
                <span class="font-medium text-gray-700 dark:text-gray-300" x-text="statusMessage"></span>
                <span class="font-semibold text-gray-900 dark:text-white" x-text="percent + '%'"></span>
            </div>

            {{-- Progress Bar --}}
            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 p-0.5 dark:bg-gray-800 border border-gray-200/60 dark:border-gray-700/60">
                <div
                    class="h-full rounded-full transition-all duration-300 ease-out"
                    :class="{
                        'bg-primary-600 dark:bg-primary-500': !isFinished && !isError,
                        'bg-emerald-500': isFinished,
                        'bg-red-500': isError
                    }"
                    :style="'width: ' + percent + '%'"
                ></div>
            </div>

            {{-- Chunk Counter Info --}}
            <div class="mt-2 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                <span x-text="'Bagian: ' + currentChunk + ' / ' + totalChunks"></span>
                <span x-text="'Total: ' + totalRecords + ' data'"></span>
            </div>

            {{-- Error Message Box --}}
            <template x-if="isError && errorMessage">
                <div class="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-700 dark:bg-red-950/40 dark:text-red-400 border border-red-200 dark:border-red-900">
                    <p class="font-semibold">Terjadi Kesalahan</p>
                    <p x-text="errorMessage" class="mt-0.5"></p>
                </div>
            </template>
        </div>

        {{-- Actions Footer --}}
        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-gray-100 dark:border-gray-800">
            <template x-if="!isFinished && !isError">
                <button
                    type="button"
                    @click="cancel()"
                    :disabled="isCancelling"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 disabled:opacity-50"
                >
                    <span x-show="!isCancelling">Batal</span>
                    <span x-show="isCancelling" class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Membatalkan...
                    </span>
                </button>
            </template>

            <template x-if="isError">
                <button
                    type="button"
                    @click="close()"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Tutup
                </button>
            </template>

            <template x-if="isFinished">
                <button
                    type="button"
                    @click="close()"
                    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                >
                    Selesai
                </button>
            </template>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('exportProgressModal', (config) => ({
        isOpen: false,
        isFinished: false,
        isError: false,
        isCancelling: false,
        token: null,
        type: 'izin',
        format: 'pdf',
        tipeLaporan: 'lengkap',
        totalRecords: 0,
        totalChunks: 1,
        currentChunk: 0,
        percent: 0,
        title: 'Membuat Dokumen Laporan',
        subtitle: 'Sedang memproses data...',
        statusMessage: 'Menyiapkan berkas...',
        errorMessage: '',
        abortController: null,

        startExport(detail) {
            // Livewire dispatches event parameters as an array in event.detail
            let payload = detail;
            if (Array.isArray(detail)) {
                payload = detail[0] || {};
            } else if (detail && typeof detail === 'object' && detail.detail) {
                payload = Array.isArray(detail.detail) ? detail.detail[0] : detail.detail;
            }
            payload = payload || {};

            const token = payload.token || (detail && detail.token) || null;
            if (!token) {
                console.error('Export Progress: Token is missing from event payload', detail);
                this.isOpen = true;
                this.isError = true;
                this.errorMessage = 'Sesi ekspor tidak valid (token kosong). Silakan muat ulang halaman dan coba lagi.';
                this.statusMessage = 'Gagal memulai ekspor.';
                return;
            }

            this.isOpen = true;
            this.isFinished = false;
            this.isError = false;
            this.isCancelling = false;
            this.token = token;
            this.type = payload.type || 'izin';
            this.format = payload.format || 'pdf';
            this.tipeLaporan = payload.tipe_laporan || payload.tipeLaporan || 'lengkap';
            this.totalRecords = payload.total || payload.total_count || 0;
            this.totalChunks = payload.totalChunks || payload.total_chunks || 1;
            this.currentChunk = 0;
            this.percent = 0;
            this.errorMessage = '';
            this.abortController = new AbortController();

            const jenisDoc = (this.type === 'icv') ? 'Pengajuan ICV' : 'Pengajuan Izin';
            const tipeText = (this.tipeLaporan === 'lengkap') ? 'Laporan Lengkap' : 'Laporan Ringkas';
            this.title = `Export ${jenisDoc}`;
            this.subtitle = `${tipeText} (${this.format.toUpperCase()})`;
            this.statusMessage = 'Memulai proses pembuatan dokumen...';

            this.processNextChunk(0);
        },

        async processNextChunk(chunkIndex) {
            if (this.isCancelling || !this.isOpen) return;

            if (chunkIndex >= this.totalChunks) {
                // Semua chunk selesai -> Finalize
                await this.finalizeExport();
                return;
            }

            this.currentChunk = chunkIndex + 1;
            this.statusMessage = `Memproses bagian ${this.currentChunk} dari ${this.totalChunks}...`;

            try {
                const response = await fetch(config.processUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        token: this.token,
                        chunk_index: chunkIndex,
                        _token: config.csrfToken
                    }),
                    signal: this.abortController.signal
                });

                if (!response.ok) {
                    const errData = await response.json().catch(() => ({}));
                    throw new Error(errData.message || `Gagal pada bagian ${chunkIndex + 1}`);
                }

                const data = await response.json();
                this.percent = data.percent;
                if (data.message) {
                    this.statusMessage = data.message;
                }

                // Process next chunk
                await this.processNextChunk(chunkIndex + 1);
            } catch (err) {
                if (err.name === 'AbortError') return;
                this.isError = true;
                this.errorMessage = err.message || 'Terjadi kesalahan saat memproses dokumen.';
                this.statusMessage = 'Pembuatan dokumen gagal.';
            }
        },

        async finalizeExport() {
            this.statusMessage = (this.format === 'pdf')
                ? 'Menggabungkan bagian PDF...'
                : 'Menyusun berkas Excel...';
            this.percent = 95;

            try {
                const response = await fetch(config.finalizeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        token: this.token,
                        _token: config.csrfToken
                    }),
                    signal: this.abortController.signal
                });

                if (!response.ok) {
                    const errData = await response.json().catch(() => ({}));
                    throw new Error(errData.message || 'Gagal menyatukan dokumen.');
                }

                const data = await response.json();
                this.percent = 100;
                this.isFinished = true;
                this.statusMessage = 'Selesai! Mengunduh dokumen...';

                // Trigger browser file download
                if (data.downloadUrl) {
                    const link = document.createElement('a');
                    link.href = data.downloadUrl;
                    link.download = data.filename || 'laporan';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                // Auto-close after brief delay
                setTimeout(() => {
                    if (this.isOpen && this.isFinished) {
                        this.close();
                    }
                }, 1600);
            } catch (err) {
                if (err.name === 'AbortError') return;
                this.isError = true;
                this.errorMessage = err.message || 'Terjadi kesalahan saat menggabungkan berkas.';
                this.statusMessage = 'Penggabungan dokumen gagal.';
            }
        },

        async cancel() {
            this.isCancelling = true;
            this.statusMessage = 'Membatalkan proses...';

            if (this.abortController) {
                this.abortController.abort();
            }

            if (this.token) {
                try {
                    await fetch(config.cancelUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            token: this.token,
                            _token: config.csrfToken
                        })
                    });
                } catch (e) {
                    // Ignore cancel errors
                }
            }

            this.close();
        },

        close() {
            this.isOpen = false;
            this.isCancelling = false;
            this.isFinished = false;
            this.isError = false;
        }
    }));
});
</script>
