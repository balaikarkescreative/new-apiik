{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/components/layout/auth.blade.php --}}
@php
    use App\Models\Visitor;

    $backgroundUrl = asset('images/auth-bg.jpg');

    // Ambil data visitor untuk halaman login
    $visitorLogin = Visitor::where('halaman', 'halaman utama')->first();
    $totalPengunjungLogin = $visitorLogin ? $visitorLogin->jumlah_pengunjung : 0;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">

    <div class="min-h-screen flex flex-col bg-cover bg-center bg-no-repeat bg-fixed bg-blend-overlay bg-[rgba(198,255,255,0.2)] dark:bg-[rgba(30,41,59,0.3)]"
         style="background-image: url('{{ $backgroundUrl }}');"
    >
        {{-- Top Bar with Logos --}}
        @if (($hasTopbar ?? true) && filament()->auth()->check())
            {{-- Authenticated state - show notifications on right --}}
            <div class="absolute end-0 top-0 flex h-12 sm:h-16 items-center gap-x-2 sm:gap-x-4 pe-2 sm:pe-4 md:pe-6 lg:pe-8 z-40">
                @if (filament()->hasDatabaseNotifications())
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications()
                    ])
                @endif
            </div>

            {{-- Kemenkes Logo on left when authenticated --}}
            <div class="absolute start-0 top-0 flex h-12 sm:h-16 items-center gap-x-2 sm:gap-x-4 ps-2 sm:ps-4 md:ps-6 lg:ps-8 my-2 sm:my-4 z-40">
                <img src="{{ asset('storage/assets/site_logo.png') }}"
                     alt="Logo Kemenkes"
                     class="h-8 sm:h-10 md:h-12 w-auto transition-transform duration-200 ease-in-out hover:scale-110 dark:brightness-110">
            </div>
        @else
            {{-- Not authenticated - show all logos --}}
            {{-- Kemenkes Logo on left --}}
            <div class="absolute start-0 top-0 flex h-12 sm:h-16 items-center gap-x-2 sm:gap-x-4 ps-2 sm:ps-4 md:ps-6 lg:ps-8 my-2 sm:my-4 z-40">
                <img src="{{ asset('storage/assets/site_logo.png') }}"
                     alt="Logo Kemenkes"
                     class="h-8 sm:h-10 md:h-12 w-auto transition-transform duration-200 ease-in-out hover:scale-110 dark:brightness-110">
            </div>

            {{-- Institutional Logos on right --}}
            <div class="absolute end-0 top-0 flex h-12 sm:h-16 items-center gap-x-1 sm:gap-x-4 pe-2 sm:pe-4 md:pe-6 lg:pe-8 my-2 sm:my-4 z-40">
                <div class="flex items-center gap-x-1 sm:gap-x-3 md:gap-x-6">
                    <img src="{{ asset('images/logo_anti_korupsi_bkk.png') }}"
                         alt="Logo Anti Korupsi"
                         class="h-8 sm:h-12 md:h-16 w-auto transition-transform duration-200 ease-in-out hover:scale-110 dark:brightness-110">
                    <img src="{{ asset('images/LOGO_KARANTINA.png') }}"
                         alt="Logo Karantina"
                         class="h-8 sm:h-12 md:h-16 w-auto transition-transform duration-200 ease-in-out hover:scale-110 dark:brightness-110">
                </div>
            </div>
        @endif

        {{-- Main Content Area with 2 Grid Layout --}}
        <div class="flex-1 flex items-center justify-center p-2 sm:p-4 lg:p-8 pt-16 sm:pt-20">
            <div class="w-full max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8 xl:gap-12 items-stretch">

                    {{-- Left Grid - ICV Section --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-lg sm:shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                        {{-- Green top border --}}
                        <div class="h-1 bg-emerald-500 dark:bg-emerald-400"></div>

                        {{-- Card Content --}}
                        <div class="p-4 sm:p-6 lg:p-8 text-center">
                            {{-- Icon --}}
                            <div class="mx-auto mb-4 sm:mb-6 w-12 sm:w-14 lg:w-16 h-12 sm:h-14 lg:h-16 bg-emerald-500 dark:bg-emerald-600 rounded-xl sm:rounded-2xl flex items-center justify-center">
                                <x-filament::icon
                                    icon="heroicon-o-building-office-2"
                                    class="w-6 sm:w-7 lg:w-8 h-6 sm:h-7 lg:h-8 text-white"
                                />
                            </div>

                            {{-- Title --}}
                            <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800 dark:text-gray-100 mb-3 sm:mb-4">Layanan Pengajuan ICV</h2>

                            {{-- Description --}}
                            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 leading-relaxed mb-6 sm:mb-8">
                                Khusus untuk klinik dan rumah sakit yang akan mengajukan izin dan ICV.
                            </p>

                            {{-- Buttons --}}
                            <div class="space-y-3">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>

                    {{-- Right Grid - Travel Section --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-lg sm:shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                        {{-- Blue top border --}}
                        <div class="h-1 bg-blue-500 dark:bg-blue-400"></div>

                        {{-- Card Content --}}
                        <div class="p-4 sm:p-6 lg:p-8 text-center">
                            {{-- Icon --}}
                            <div class="mx-auto mb-4 sm:mb-6 w-12 sm:w-14 lg:w-16 h-12 sm:h-14 lg:h-16 bg-blue-500 dark:bg-blue-600 rounded-xl sm:rounded-2xl flex items-center justify-center">
                                <x-filament::icon
                                    icon="heroicon-o-globe-alt"
                                    class="w-6 sm:w-7 lg:w-8 h-6 sm:h-7 lg:h-8 text-white"
                                />
                            </div>

                            {{-- Title --}}
                            <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800 dark:text-gray-100 mb-3 sm:mb-4">Perjalanan Internasional</h2>

                            {{-- Description --}}
                            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 leading-relaxed mb-6 sm:mb-8">
                                Layanan untuk pelaku perjalanan internasional termasuk umroh, haji, pelajar, pekerja dan keperluan lainnya ke luar negeri.
                            </p>

                            {{-- Buttons --}}
                            <div class="space-y-3">
                                <a
                                    href="https://travel.bkkbandung.net/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-full bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg sm:rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2 hover:shadow-xl hover:scale-[1.02] text-sm sm:text-base"
                                >
                                    <span>Klik Disini</span>
                                    <x-filament::icon
                                        icon="heroicon-o-arrow-top-right-on-square"
                                        class="w-4 sm:w-5 h-4 sm:h-5 flex-shrink-0"
                                    />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Video Tutorial Button - Responsive --}}
        <div class="fixed bottom-16 sm:bottom-20 left-2 sm:left-4 lg:left-6 z-40">
            <button
                x-data="{ modalOpen: false }"
                @open-modal.window="modalOpen = $event.detail.id === 'video-tutorial-modal'"
                @close-modal.window="modalOpen = false"
                @click="$dispatch('open-modal', { id: 'video-tutorial-modal' })"
                aria-label="Video Panduan Penggunaan Aplikasi"
                class="flex items-center gap-1 sm:gap-2 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 text-white px-2 sm:px-3 lg:px-4 py-2 sm:py-2.5 lg:py-3 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 text-xs sm:text-sm font-medium max-w-[280px] sm:max-w-none"
                :class="{ 'opacity-0 pointer-events-none': modalOpen }"
            >
                {{-- Play Icon --}}
                <x-filament::icon
                    icon="heroicon-s-play"
                    class="w-4 sm:w-[18px] h-4 sm:h-[18px] flex-shrink-0"
                />
                {{-- Button Text - Hide on very small screens, show shortened text on small screens --}}
                <span class="hidden sm:inline text-xs sm:text-sm">Video Panduan Penggunaan Aplikasi</span>
            </button>
        </div>

        {{-- Video Tutorial Modal --}}
        <x-filament::modal
            id="video-tutorial-modal"
            width="6xl"
            :close-by-clicking-away="true"
        >
            <x-slot name="heading">
                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="p-1.5 sm:p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <x-filament::icon
                            icon="heroicon-o-play-circle"
                            class="h-5 sm:h-6 w-5 sm:w-6 text-red-600 dark:text-red-400"
                        />
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">Video Panduan Penggunaan Aplikasi</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Tutorial lengkap cara menggunakan aplikasi APIIK</p>
                    </div>
                </div>
            </x-slot>

            <div class="space-y-4">
                {{-- Video Container --}}
                <div class="relative w-full aspect-video bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                    <iframe
                        src="https://drive.google.com/file/d/14mO6VCf20YZWlgXdTq4Y3baIelN1ZoxO/preview"
                        class="absolute inset-0 w-full h-full border-0"
                        allow="autoplay; encrypted-media"
                        allowfullscreen
                        title="Video Panduan Penggunaan Aplikasi ICV"
                    ></iframe>
                </div>
            </div>
        </x-filament::modal>

        @include('filament.pages.auth.wbs')

        {{-- Footer Start --}}
        @include('filament.components.layout.footer')
        {{-- Footer End --}}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire->getRenderHookScopes()) }}
    </div>

    {{-- Responsive Styles --}}
    @push('styles')
    <style>
        /* Custom breakpoint for extra small screens */
        @media (min-width: 480px) {
            .xs\:inline {
                display: inline;
            }
        }

        /* Logo responsive adjustments */
        @media (max-width: 640px) {
            /* Reduce gaps between header elements on mobile */
            .absolute.end-0.top-0 .flex.items-center {
                gap: 0.25rem !important;
            }

            .absolute.start-0.top-0 .flex.items-center {
                gap: 0.5rem !important;
            }

            /* Ensure logos don't overlap on very small screens */
            .absolute.start-0.top-0 {
                max-width: 45%;
            }

            .absolute.end-0.top-0 {
                max-width: 50%;
            }

            /* Adjust institutional logos container */
            .absolute.end-0.top-0 .flex.items-center.gap-x-1 {
                overflow: hidden;
            }
        }

        /* Very small screens (iPhone SE and similar) */
        @media (max-width: 375px) {
            .absolute.start-0.top-0 {
                max-width: 40%;
            }

            .absolute.end-0.top-0 {
                max-width: 55%;
            }

            /* Make institutional logos even smaller on tiny screens */
            .absolute.end-0.top-0 img {
                height: 1.75rem !important; /* h-7 */
            }

            /* Adjust video tutorial button for tiny screens */
            .fixed.bottom-16 button {
                padding: 0.375rem 0.5rem !important;
                font-size: 0.625rem !important;
                max-width: 200px;
            }
        }

        /* Ensure proper grid behavior on mobile */
        @media (max-width: 1024px) {
            .grid-cols-1.lg\\:grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }

        /* Animation for grid items */
        .grid > div {
            animation: fadeInUp 0.8s ease-out;
        }

        .grid > div:nth-child(1) {
            animation-delay: 0.1s;
        }

        .grid > div:nth-child(2) {
            animation-delay: 0.3s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modal backdrop */
        [x-data] .fi-modal {
            backdrop-filter: blur(4px);
            z-index: 9999 !important;
        }
        
        /* Ensure modal overlay is above everything */
        .fi-modal-window {
            z-index: 10000 !important;
        }
        
        /* Modal backdrop should be above fixed elements */
        .fi-modal-backdrop {
            z-index: 9998 !important;
        }

        /* Card hover effects */
        .bg-white.rounded-xl,
        .bg-white.rounded-2xl,
        .dark .bg-white.rounded-xl,
        .dark .bg-white.rounded-2xl {
            transition: all 0.3s ease;
        }

        .bg-white.rounded-xl:hover,
        .bg-white.rounded-2xl:hover,
        .dark .bg-white.rounded-xl:hover,
        .dark .bg-white.rounded-2xl:hover {
            transform: translateY(-2px);
        }

        /* Only apply stronger hover effects on larger screens */
        @media (min-width: 768px) {
            .bg-white.rounded-xl:hover,
            .bg-white.rounded-2xl:hover,
            .dark .bg-white.rounded-xl:hover,
            .dark .bg-white.rounded-2xl:hover {
                transform: translateY(-4px);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            }

            /* Dark mode card hover shadow */
            .dark .bg-white.rounded-xl:hover,
            .dark .bg-white.rounded-2xl:hover {
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            }
        }

        /* Button animations */
        button, a {
            transition: all 0.2s ease;
        }

        /* Video iframe responsive */
        .aspect-video {
            aspect-ratio: 16 / 9;
        }

        /* Modal width on mobile */
        @media (max-width: 768px) {
            [id="video-tutorial-modal"] {
                width: 95vw !important;
                max-width: 95vw !important;
            }

            [id="video-tutorial-modal"] .space-y-4 {
                padding: 0.5rem;
            }
        }

        /* Dark mode specific styles */
        .dark {
            color-scheme: dark;
        }

        /* Smooth transition for dark mode */
        * {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        /* Dark mode logo brightness adjustment */
        .dark img {
            filter: brightness(1.1) contrast(0.9);
        }

        /* Dark mode shadow adjustments */
        .dark .shadow-lg {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
        }

        .dark .shadow-xl {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        }

        .dark .shadow-2xl {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }

        /* Logo positioning and spacing */
        .absolute.start-0.top-0,
        .absolute.end-0.top-0 {
            z-index: 40;
        }
        
        /* Floating button transitions */
        .fixed button {
            transition: opacity 0.3s ease, transform 0.3s ease, background-color 0.2s ease;
        }
        
        .fixed button.opacity-0 {
            opacity: 0;
            transform: scale(0.95);
        }

        /* Ensure content doesn't overlap with fixed positioned elements */
        @media (max-width: 640px) {
            .flex-1.flex.items-center.justify-center {
                padding-bottom: 5rem; /* Account for floating buttons */
            }
        }

        /* Responsive text truncation for long button text */
        @media (max-width: 480px) {
            .fixed button span {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 150px;
            }
        }
    </style>
    @endpush
</x-filament-panels::layout.base>
