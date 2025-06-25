<div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <button id="openWbsModal"
            style="color: white; font-weight: bold; padding: 12px 16px; border-radius: 9999px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06), 0 12px 20px rgba(0, 0, 0, 0.1); display: flex; align-items: center; background-color: #dc2626; /* Ganti dengan warna yang diinginkan, misal merah */ border: none; cursor: pointer;"
            onmouseover="this.style.backgroundColor='#b91c1c';"
            onmouseout="this.style.backgroundColor='#dc2626';"
        >
            <x-heroicon-o-exclamation-circle style="width: 2.5rem; height: 2.5rem;" />
        </button>
    </div>
    {{-- Floating Button End --}}

    {{-- WBS Modal Start --}}
    <div id="wbsModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); overflow-y: auto; height: 100%; width: 100%; display: none; align-items: center; justify-content: center; z-index: 1050;">
        {{-- Modal Content --}}
        <div style="position: relative; margin-left: auto; margin-right: auto; padding: 20px; border: 1px solid #e5e7eb; width: 100%; max-width: 600px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); border-radius: 0.375rem; background-color: white;">
            {{-- Close Button (X) --}}
            <button id="closeWbsModalX" style="position: absolute; top: 10px; right: 10px; margin-top: 0.5rem; margin-right: 0.5rem; color: #9ca3af; background: none; border: none; cursor: pointer;"
                onmouseover="this.style.color='#6b7280';"
                onmouseout="this.style.color='#9ca3af';"
            >
                <x-heroicon-o-x-mark style="width: 1.5rem; height: 1.5rem;" />
            </button>

            <div style="margin-top: 0.75rem; text-align: center;">
                <h3 style="font-size: 1.125rem; line-height: 1.5rem; font-weight: 500; color: #111827;">Informasi Whistleblowing System (WBS)</h3>
                <div style="margin-top: 0.5rem; padding-left: 1.75rem; padding-right: 1.75rem; padding-top: 0.75rem; padding-bottom: 0.75rem;">
                    {{-- Ganti 'path/to/your/gambar_wbs.png' dengan path gambar WBS Anda --}}
                    <img src="{{ asset('images/wbs.png') }}" alt="Gambar WBS" style="margin-left: auto; margin-right: auto; width: 100%; height: auto; max-height: 384px; object-fit: contain;">
                </div>
                <div style="display: flex; align-items: center; justify-content: center; padding-left: 1rem; padding-right: 1rem; padding-top: 0.75rem; padding-bottom: 0.75rem;">
                    <a href="https://wbs.kemkes.go.id/" target="_blank" style="color: #2563eb; font-weight: 500; text-decoration: underline;"
                       onmouseover="this.style.color='#1d4ed8';"
                       onmouseout="this.style.color='#2563eb';"
                    >
                        Klik di sini untuk informasi lebih lanjut
                    </a>
                </div>
                <div style="display: flex; align-items: center; justify-content: center; padding-left: 1rem; padding-right: 1rem; padding-top: 0.75rem; padding-bottom: 0.75rem;">
                    <button id="closeWbsModalButton" style="padding: 8px 16px; background-color: #6b7280; color: white; font-size: 1rem; font-weight: 500; border-radius: 0.375rem; width: 100%; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); border: none; cursor: pointer;"
                        onmouseover="this.style.backgroundColor='#4b5563';"
                        onmouseout="this.style.backgroundColor='#6b7280';"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- WBS Modal End --}}

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wbsModal = document.getElementById('wbsModal');
            const openWbsModalButton = document.getElementById('openWbsModal');
            const closeWbsModalButton = document.getElementById('closeWbsModalButton'); // Tombol Tutup di bawah
            const closeWbsModalXButton = document.getElementById('closeWbsModalX'); // Tombol X di atas

            function openModal() {
                if (wbsModal) wbsModal.style.display = 'flex';
            }

            function closeModal() {
                if (wbsModal) wbsModal.style.display = 'none';
            }

            if (openWbsModalButton) {
                openWbsModalButton.addEventListener('click', openModal);
            }

            if (closeWbsModalButton) {
                closeWbsModalButton.addEventListener('click', closeModal);
            }

            if (closeWbsModalXButton) {
                closeWbsModalXButton.addEventListener('click', closeModal);
            }

            // Optional: Close modal when clicking outside of it
            window.addEventListener('click', (event) => {
                if (event.target === wbsModal) {
                    closeModal();
                }
            });

            // Optional: Close modal with Escape key
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && wbsModal && wbsModal.style.display === 'flex') {
                    closeModal();
                }
            });
        });
    </script>
    @endpush
