{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/pages/auth/wbs.blade.php --}}

{{-- Floating WBS Button - Responsive --}}
<div class="fixed bottom-3 sm:bottom-5 right-2 sm:right-5 z-[30]">
    <button id="openWbsModal"
        x-data="{
            modalOpen: false,
            wbsModalOpen: false
        }"
        @open-modal.window="modalOpen = $event.detail.id === 'video-tutorial-modal'"
        @close-modal.window="modalOpen = false"
        class="flex items-center justify-center text-white font-bold p-2 sm:p-3 rounded-full shadow-lg shadow-red-500/20 bg-red-600 hover:bg-red-700 border-none cursor-pointer transition-all duration-300 hover:scale-105"
        :class="{ 'opacity-0 pointer-events-none': modalOpen || wbsModalOpen }"
        aria-label="Buka Informasi Whistleblowing System"
        title="Whistleblowing System (WBS)"
    >
        <x-heroicon-o-exclamation-circle class="w-6 sm:w-8 lg:w-10 h-6 sm:h-8 lg:h-10" />
    </button>
</div>

{{-- WBS Modal - Responsive --}}
<div id="wbsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm overflow-y-auto h-full w-full hidden items-center justify-center z-[9000] p-2 sm:p-4">
    {{-- Modal Content --}}
    <div class="relative mx-auto border border-gray-200 dark:border-gray-700 w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg xl:max-w-xl shadow-xl rounded-lg sm:rounded-xl bg-white dark:bg-gray-800">
        {{-- Close Button (X) --}}
        <button id="closeWbsModalX"
            class="absolute top-2 right-2 sm:top-3 sm:right-3 text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-200 bg-transparent border-none cursor-pointer transition-colors duration-200 z-10 p-1"
            aria-label="Tutup Modal">
            <x-heroicon-o-x-mark class="w-5 sm:w-6 h-5 sm:h-6" />
        </button>

        <div class="p-4 sm:p-5">
            <div class="text-center">
                {{-- Header --}}
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4 pr-8">
                    Informasi Whistleblowing System (WBS)
                </h3>

                {{-- WBS Image Container --}}
                <div class="mb-4 sm:mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2 sm:p-3">
                        <img src="{{ asset('images/wbs.png') }}"
                             alt="Gambar WBS"
                             class="mx-auto w-full h-auto max-h-[250px] sm:max-h-[300px] md:max-h-[400px] object-contain rounded">
                    </div>
                </div>

                {{-- Link --}}
                <div class="mb-4 sm:mb-6">
                    <a href="https://wbs.kemkes.go.id/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-1 sm:gap-2 text-blue-600 dark:text-blue-400 font-medium hover:text-blue-700 dark:hover:text-blue-300 transition-colors duration-200 text-sm sm:text-base underline decoration-2 underline-offset-2 hover:decoration-blue-700 dark:hover:decoration-blue-300">
                        <span>Informasi Lebih Lanjut</span>
                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 flex-shrink-0" />
                    </a>
                </div>

                {{-- Close Button --}}
                <div class="flex justify-center">
                    <button id="closeWbsModalButton"
                        class="w-full sm:w-auto min-w-[120px] py-2 sm:py-2.5 px-4 sm:px-6 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-700 text-white text-sm sm:text-base font-medium rounded-lg shadow-sm border-none cursor-pointer transition-all duration-200 hover:scale-[1.02]">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wbsModal = document.getElementById('wbsModal');
    const openWbsModalButton = document.getElementById('openWbsModal');
    const closeWbsModalButton = document.getElementById('closeWbsModalButton');
    const closeWbsModalXButton = document.getElementById('closeWbsModalX');

    function openModal() {
        if (wbsModal && openWbsModalButton) {
            wbsModal.classList.replace('hidden', 'flex');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling

            // Update Alpine.js state
            if (openWbsModalButton._x_dataStack && openWbsModalButton._x_dataStack[0]) {
                openWbsModalButton._x_dataStack[0].wbsModalOpen = true;
            }
        }
    }

    function closeModal() {
        if (wbsModal && openWbsModalButton) {
            wbsModal.classList.replace('flex', 'hidden');
            document.body.style.overflow = ''; // Restore scrolling

            // Update Alpine.js state
            if (openWbsModalButton._x_dataStack && openWbsModalButton._x_dataStack[0]) {
                openWbsModalButton._x_dataStack[0].wbsModalOpen = false;
            }
        }
    }

    // Event listeners
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

    // Handle touch events for better mobile experience
    let touchStartY = 0;
    wbsModal?.addEventListener('touchstart', (e) => {
        if (e.target === wbsModal) {
            touchStartY = e.touches[0].clientY;
        }
    });

    wbsModal?.addEventListener('touchend', (e) => {
        if (e.target === wbsModal) {
            const touchEndY = e.changedTouches[0].clientY;
            const deltaY = touchEndY - touchStartY;
            // Close modal if swiped down significantly
            if (deltaY > 100) {
                closeModal();
            }
        }
    });
});
</script>
@endpush

@push('styles')
<style>
    /* Additional responsive styles for WBS modal */
    @media (max-width: 640px) {
        /* Ensure modal doesn't take full height on mobile */
        #wbsModal > div {
            max-height: 90vh;
            overflow-y: auto;
            margin: auto 0.5rem;
        }

        /* Adjust floating button position to avoid overlap with other floating elements */
        .fixed.bottom-3.right-2 {
            bottom: 4rem; /* Move up to avoid footer overlap */
        }
    }

    /* Very small screens adjustments */
    @media (max-width: 375px) {
        #wbsModal > div {
            margin: auto 0.25rem;
            max-height: 95vh;
        }

        /* Make WBS button slightly smaller on tiny screens */
        .fixed.bottom-3.right-2 button {
            padding: 0.5rem;
        }

        .fixed.bottom-3.right-2 button svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
        }
    }

    /* Large screens - add some breathing room */
    @media (min-width: 1024px) {
        #wbsModal > div {
            max-height: 80vh;
        }
    }

    /* Smooth transitions for modal */
    #wbsModal {
        transition: opacity 0.3s ease, backdrop-filter 0.3s ease;
    }

    #wbsModal > div {
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    /* When modal is hidden, scale down slightly */
    #wbsModal.hidden > div {
        transform: scale(0.95);
        opacity: 0;
    }

    /* When modal is shown, scale to normal */
    #wbsModal.flex > div {
        transform: scale(1);
        opacity: 1;
    }

    /* Improve button hover effects on touch devices */
    @media (hover: none) {
        .fixed button:hover {
            transform: none;
        }

        .fixed button:active {
            transform: scale(0.95);
        }
    }

    /* Dark mode specific adjustments for better contrast */
    .dark #wbsModal img {
        border-radius: 0.5rem;
        background: rgba(255, 255, 255, 0.1);
        padding: 0.25rem;
    }

    /* Focus styles for accessibility */
    #openWbsModal:focus-visible,
    #closeWbsModalButton:focus-visible,
    #closeWbsModalX:focus-visible {
        outline: 2px solid #3B82F6;
        outline-offset: 2px;
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        #wbsModal > div {
            border: 2px solid currentColor;
        }

        .fixed button {
            border: 2px solid currentColor;
        }
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        #wbsModal,
        #wbsModal > div,
        .fixed button {
            transition: none;
        }

        .fixed button:hover {
            transform: none;
        }
    }
</style>
@endpush
