<div class="fixed bottom-5 right-5 z-[1000]">
    <button id="openWbsModal"
        class="flex items-center text-white font-bold py-3 px-4 rounded-full shadow-lg shadow-red-500/20 bg-red-600 hover:bg-red-700 border-none cursor-pointer transition-colors duration-200"
    >
        <x-heroicon-o-exclamation-circle class="w-10 h-10" />
    </button>
</div>
{{-- Floating Button End --}}

{{-- WBS Modal Start --}}
<div id="wbsModal" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full hidden items-center justify-center z-[1050]">
    {{-- Modal Content --}}
    <div class="relative mx-auto p-5 border border-gray-200 w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl shadow-xl rounded-md bg-white dark:bg-gray-800 dark:border-gray-700">
        {{-- Close Button (X) --}}
        <button id="closeWbsModalX" class="absolute top-2.5 right-2.5 mt-2 mr-2 text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-200 bg-transparent border-none cursor-pointer transition-colors duration-200">
            <x-heroicon-o-x-mark class="w-6 h-6" />
        </button>

        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Informasi Whistleblowing System (WBS)</h3>
            <div class="mt-2 px-4 py-3">
                {{-- Gambar WBS --}}
                <img src="{{ asset('images/wbs.png') }}" alt="Gambar WBS" class="mx-auto w-full h-auto max-h-[400px] object-contain">
            </div>
            <div class="flex items-center justify-center px-4 py-3">
                <a
                   href="https://wbs.kemkes.go.id/"
                   target="_blank"
                   class="text-blue-600 dark:text-blue-400 font-medium underline hover:text-blue-700 dark:hover:text-blue-300 transition-colors duration-200"
                >
                    Klik di sini untuk informasi lebih lanjut
                </a>
            </div>
            <div class="flex items-center justify-center px-4 py-3">
                <button
                    id="closeWbsModalButton"
                    class="py-2 px-4 bg-gray-500 hover:bg-gray-600 text-white text-base font-medium rounded-md w-full shadow-sm border-none cursor-pointer transition-colors duration-200"
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
        const closeWbsModalButton = document.getElementById('closeWbsModalButton');
        const closeWbsModalXButton = document.getElementById('closeWbsModalX');

        function openModal() {
            if (wbsModal) wbsModal.classList.replace('hidden', 'flex');
        }

        function closeModal() {
            if (wbsModal) wbsModal.classList.replace('flex', 'hidden');
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

        // Close modal when clicking outside of it
        window.addEventListener('click', (event) => {
            if (event.target === wbsModal) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && wbsModal && wbsModal.classList.contains('flex')) {
                closeModal();
            }
        });
    });
</script>
@endpush
