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
            <div class="absolute end-0 top-0 flex h-16 items-center gap-x-4 pe-4 md:pe-6 lg:pe-8">
                @if (filament()->hasDatabaseNotifications())
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications()
                    ])
                @endif
            </div>

            {{-- Kemenkes Logo on left when authenticated --}}
            <div class="absolute start-0 top-0 flex h-16 items-center gap-x-4 ps-4 md:ps-6 lg:ps-8 my-4">
                <img src="{{ asset('storage/assets/site_logo.png') }}" alt="Logo Kemenkes" class="h-12 w-auto transition-transform duration-200 ease-in-out hover:scale-110 dark:brightness-110">
            </div>
        @else
            {{-- Not authenticated - show all logos --}}
            {{-- Kemenkes Logo on left --}}
            <div class="absolute start-0 top-0 flex h-16 items-center gap-x-4 ps-4 md:ps-6 lg:ps-8 my-4">
                <img src="{{ asset('storage/assets/site_logo.png') }}" alt="Logo Kemenkes" class="h-12 w-auto transition-transform duration-200 ease-in-out hover:scale-110 dark:brightness-110">
            </div>

            {{-- Institutional Logos on right --}}
            <div class="absolute end-0 top-0 flex h-16 items-center gap-x-4 pe-4 md:pe-6 lg:pe-8 my-4 sm:px-4">
                <div class="flex items-center gap-x-6">
                    <img src="{{ asset('images/logo_anti_korupsi_bkk.png') }}" alt="Logo Anti Korupsi" class="h-16 w-auto transition-transform duration-200 ease-in-out hover:scale-110 dark:brightness-110">
                    <img src="{{ asset('images/LOGO_KARANTINA.png') }}" alt="Logo Karantina" class="h-16 w-auto transition-transform duration-200 ease-in-out hover:scale-110 dark:brightness-110">
                </div>
            </div>
        @endif

        {{-- Main Content Area with 2 Grid Layout --}}
        <div class="flex-1 flex items-center justify-center p-4 lg:p-8">
            <div class="w-full max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-stretch">

                    {{-- Left Grid - ICV Section --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                        {{-- Green top border --}}
                        <div class="h-1 bg-emerald-500 dark:bg-emerald-400"></div>

                        {{-- Card Content --}}
                        <div class="p-8 text-center">
                            {{-- Icon --}}
                            <div class="mx-auto mb-6 w-16 h-16 bg-emerald-500 dark:bg-emerald-600 rounded-2xl flex items-center justify-center">
                                <x-filament::icon
                                    icon="heroicon-o-building-office-2"
                                    class="w-8 h-8 text-white"
                                />
                            </div>

                            {{-- Title --}}
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">Layanan Pengajuan ICV</h2>

                            {{-- Description --}}
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-8">
                                Khusus untuk klinik dan rumah sakit yang akan mengajukan izin dan ICV.
                            </p>

                            {{-- Buttons --}}
                            <div class="space-y-3">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>

                    {{-- Right Grid - Travel Section --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                        {{-- Blue top border --}}
                        <div class="h-1 bg-blue-500 dark:bg-blue-400"></div>

                        {{-- Card Content --}}
                        <div class="p-8 text-center">
                            {{-- Icon --}}
                            <div class="mx-auto mb-6 w-16 h-16 bg-blue-500 dark:bg-blue-600 rounded-2xl flex items-center justify-center">
                                <x-filament::icon
                                    icon="heroicon-o-globe-alt"
                                    class="w-8 h-8 text-white"
                                />
                            </div>

                            {{-- Title --}}
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">Perjalanan Internasional</h2>

                            {{-- Description --}}
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-8">
                                Layanan untuk pelaku perjalanan internasional termasuk umroh, haji, pelajar, pekerja dan keperluan lainnya ke luar negeri.
                            </p>

                            {{-- Buttons --}}
                            <div class="space-y-3">
                                <a
                                    href="https://travel.bkkbandung.net/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-full bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2 hover:shadow-xl hover:scale-[1.02]"
                                >
                                    <span>Klik Disini</span>
                                    <x-filament::icon
                                        icon="heroicon-o-arrow-top-right-on-square"
                                        class="w-5 h-5 flex-shrink-0"
                                    />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Video Tutorial Button --}}
        <div class="fixed bottom-20 left-6 z-50 sm:bottom-[90px]">
            <button
                x-data
                @click="$dispatch('open-modal', { id: 'video-tutorial-modal' })"
                aria-label="Video Panduan Penggunaan Aplikasi"
                class="flex items-center gap-2 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 text-white px-4 py-3 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 text-sm font-medium"
            >
                {{-- Play Icon --}}
                <x-filament::icon
                    icon="heroicon-s-play"
                    class="w-[18px] h-[18px] sm:w-[18px] sm:h-[18px]"
                />
                {{-- Button Text --}}
                <span class="sm:inline text-xs sm:text-sm">Video Panduan Penggunaan Aplikasi</span>
            </button>
        </div>

        {{-- Video Tutorial Modal --}}
        <x-filament::modal
            id="video-tutorial-modal"
            width="6xl"
            :close-by-clicking-away="true"
        >
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <x-filament::icon
                            icon="heroicon-o-play-circle"
                            class="h-6 w-6 text-red-600 dark:text-red-400"
                        />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Video Panduan Penggunaan Aplikasi</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tutorial lengkap cara menggunakan aplikasi APIIK</p>
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
        /* Custom responsive adjustments */
        @media (max-width: 640px) {
            .absolute.end-0.top-0 .flex.items-center.gap-x-2 {
                gap: 4px !important;
            }

            /* Adjust logo sizes on mobile */
            .absolute.start-0.top-0 img,
            .absolute.end-0.top-0 img {
                height: 2.5rem !important; /* h-10 */
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
        }

        /* Card hover effects */
        .bg-white.rounded-2xl,
        .dark .bg-white.rounded-2xl {
            transition: all 0.3s ease;
        }

        .bg-white.rounded-2xl:hover,
        .dark .bg-white.rounded-2xl:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        /* Dark mode card hover shadow */
        .dark .bg-white.rounded-2xl:hover {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
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
    </style>
    @endpush
</x-filament-panels::layout.base>
