{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/components/layout/auth.blade.php --}}
@php
    use App\Models\Visitor;

    $backgroundUrl = asset('images/auth-bg.jpg');

    // Ambil data visitor untuk halaman login
    $visitorLogin = Visitor::where('halaman', 'halaman utama')->first();
    $totalPengunjungLogin = $visitorLogin ? $visitorLogin->jumlah_pengunjung : 0;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="auth-layout min-h-screen flex flex-col bg-cover bg-center bg-no-repeat bg-fixed bg-blend-overlay bg-[rgba(198,255,255,0.2)] dark:bg-[rgba(30,41,59,0.3)]"
         style="background-image: url('{{ $backgroundUrl }}');">
        
        {{-- HUT RI Banner --}}
        @include('components.hut-ri-banner')

        {{-- Header Section --}}
        @include('filament.components.layout.header')

        {{-- Main Content Container --}}
        <main class="main-content flex-1 flex items-center justify-center p-4 lg:p-8 pt-20 lg:pt-24">
            <div class="content-wrapper w-full max-w-8xl mx-auto">
                
                {{-- Service Cards Grid --}}
                <div class="services-grid grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 xl:gap-12 items-start">
                    
                    {{-- ICV Service Card --}}
                    <div class="service-card icv-card">
                        <div class="card-border bg-emerald-500 dark:bg-emerald-400"></div>
                        
                        <div class="card-content">
                            <div class="card-icon bg-emerald-500 dark:bg-emerald-600">
                                <x-filament::icon
                                    icon="heroicon-o-building-office-2"
                                    class="w-7 lg:w-8 h-7 lg:h-8 text-white"
                                />
                            </div>

                            <div class="card-header">
                                <h2 class="card-title">Layanan Pengajuan ICV</h2>
                                <p class="card-description">
                                    Khusus untuk klinik dan rumah sakit yang akan mengajukan izin dan ICV.
                                </p>
                            </div>

                            <div class="card-actions">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>

                    {{-- Travel Service Card --}}
                    <div class="service-card travel-card">
                        <div class="card-border bg-blue-500 dark:bg-blue-400"></div>
                        
                        <div class="card-content">
                            <div class="card-icon bg-blue-500 dark:bg-blue-600">
                                <x-filament::icon
                                    icon="heroicon-o-globe-alt"
                                    class="w-7 lg:w-8 h-7 lg:h-8 text-white"
                                />
                            </div>

                            <div class="card-header">
                                <h2 class="card-title">Perjalanan Internasional</h2>
                                <p class="card-description">
                                    Layanan untuk pelaku perjalanan internasional termasuk umroh, haji, pelajar, pekerja dan keperluan lainnya ke luar negeri.
                                </p>
                            </div>

                            <div class="card-actions">
                                <a href="https://travel.bkkbandung.net/" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="btn btn-primary btn-blue">
                                    <span>Klik Disini</span>
                                    <x-filament::icon
                                        icon="heroicon-o-arrow-top-right-on-square"
                                        class="w-5 h-5 flex-shrink-0"
                                    />
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Media & Tutorial Card --}}
                    <div class="service-card media-card">
                        <div class="card-border bg-gray-400 dark:bg-gray-600"></div>
                        
                        <div class="card-content">
                            {{-- Image Slider --}}
                            <div class="media-slider" 
                                 x-data="imageSlider()" 
                                 x-init="startAutoSlide()"
                                 @mouseenter="stopAutoSlide()"
                                 @mouseleave="startAutoSlide()">
                                
                                <div class="slider-container">
                                    <template x-for="(image, index) in images" :key="index">
                                        <img :src="image"
                                             :alt="'Indonesia ' + (index + 1)"
                                             class="slider-image"
                                             :class="{ 'active': index === currentIndex }">
                                    </template>

                                    {{-- Navigation --}}
                                    <button @click="prev()" class="slider-btn slider-btn-prev" aria-label="Previous">
                                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                                    </button>

                                    <button @click="next()" class="slider-btn slider-btn-next" aria-label="Next">
                                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                                    </button>

                                    {{-- Indicators --}}
                                    <div class="slider-indicators">
                                        <template x-for="(image, index) in images" :key="index">
                                            <span class="indicator" 
                                                  :class="{ 'active': index === currentIndex }"
                                                  @click="currentIndex = index"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Tutorial Button --}}
                            <div class="card-actions mt-6">
                                <button x-data="{}"
                                        @click="$dispatch('open-modal', { id: 'video-tutorial-modal' })"
                                        class="btn btn-primary btn-red">
                                    <x-filament::icon icon="heroicon-o-play-circle" class="w-5 h-5" />
                                    <span>Lihat Video Tutorial</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        {{-- Floating Elements --}}
        @include('filament.components.layout.floating-elements')

        {{-- Video Tutorial Modal --}}
        @include('filament.components.layout.video-modal')

        {{-- WBS Section --}}
        @include('filament.pages.auth.wbs')

        {{-- Footer --}}
        @include('filament.components.layout.footer')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire->getRenderHookScopes()) }}
    </div>

    {{-- Alpine.js Components --}}
    @push('scripts')
    <script>
        function imageSlider() {
            return {
                images: Array.from({{ json_encode(range(1, 6)) }}).map(i => '{{ asset('images/allindonesia') }}/' + i + '.png'),
                currentIndex: 0,
                autoSlide: true,
                interval: null,
                
                startAutoSlide() {
                    if (!this.autoSlide) return;
                    // prevent multiple intervals by clearing any existing one first
                    this.stopAutoSlide();
                    this.interval = setInterval(() => {
                        this.next();
                    }, 4000);
                },
                
                stopAutoSlide() {
                    if (this.interval) {
                        clearInterval(this.interval);
                        this.interval = null;
                    }
                },
                
                next() {
                    this.currentIndex = (this.currentIndex + 1) % this.images.length;
                },
                
                prev() {
                    this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
                }
            }
        }
    </script>
    @endpush

    {{-- Styles --}}
    @push('styles')
    <style>
        /* Layout Structure */
        .auth-layout {
            min-height: 100vh;
            position: relative;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            padding-top: 5rem;
        }

        .content-wrapper {
            width: 100%;
            max-width: 90rem;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            align-items: start; /* ensure cards align to top and don't stretch */
        }

        /* Service Cards */
        .service-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid rgba(229, 231, 235, 1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: fadeInUp 0.6s ease-out;
        }

        .service-card:nth-child(1) { animation-delay: 0.1s; }
        .service-card:nth-child(2) { animation-delay: 0.2s; }
        .service-card:nth-child(3) { animation-delay: 0.3s; }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .card-border {
            height: 3px;
            width: 100%;
        }

        .card-content {
            padding: 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            height: auto; /* allow natural height so cards don't force-equalize */
        }

        .card-icon {
            width: 4rem;
            height: 4rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            /* don't force header to grow; let content size naturally */
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .card-description {
            font-size: 1rem;
            color: #6b7280;
            line-height: 1.6;
        }

        .card-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            min-height: 48px;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            color: white;
        }

        .btn-blue {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .btn-blue:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e3a8a);
        }

        .btn-red {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .btn-red:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
        }

        /* Media Slider */
        .media-slider {
            margin-bottom: 1rem;
        }

        .slider-container {
            position: relative;
            width: 100%;
            max-width: 20rem;
            aspect-ratio: 4/5;
            border-radius: 0.75rem;
            overflow: hidden;
            margin: 0 auto;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .slider-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .slider-image.active {
            opacity: 1;
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.2s ease;
            z-index: 10;
        }

        .slider-btn:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .slider-btn-prev {
            left: 0.5rem;
        }

        .slider-btn-next {
            right: 0.5rem;
        }

        .slider-indicators {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.375rem;
            z-index: 10;
        }

        .indicator {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .indicator.active {
            background: white;
        }

        /* Responsive Design */
        @media (min-width: 768px) {
            .main-content {
                padding: 2rem;
                padding-top: 6rem;
            }

            .services-grid {
                gap: 2rem;
            }

            .card-content {
                padding: 2.5rem;
            }

            .card-icon {
                width: 4.5rem;
                height: 4.5rem;
            }
        }

        @media (min-width: 1024px) {
            .services-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 3rem;
            }

            .card-content {
                padding: 3rem;
            }

            .slider-container {
                max-width: 24rem;
            }
        }

        @media (min-width: 1280px) {
            .services-grid {
                gap: 4rem;
            }
        }

        /* Animations */
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

        /* Dark mode overrides for better contrast */
        .dark .service-card {
            background: #111827; /* Tailwind gray-900 */
            border-color: rgba(55, 65, 81, 1); /* gray-700 */
            box-shadow: 0 10px 20px -6px rgba(0,0,0,0.6), 0 6px 10px -4px rgba(0,0,0,0.45);
        }

        .dark .card-title {
            color: #f8fafc; /* gray-50 / near-white */
        }

        .dark .card-description {
            color: #9ca3af; /* gray-400 for readable secondary text */
        }

        .dark .card-border {
            /* if an element doesn't have a dark class applied, this ensures the border strip is visible */
            filter: none;
            opacity: 1;
        }

        .dark .btn-blue {
            background: linear-gradient(135deg, #2563eb, #1e40af);
        }

        .dark .btn-red {
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
        }

        .dark .card-icon {
            box-shadow: 0 6px 12px -6px rgba(0,0,0,0.6);
        }

        .dark .slider-indicators .indicator {
            background: rgba(255, 255, 255, 0.12);
        }

        .dark .indicator.active {
            background: #ffffff;
            opacity: 0.95;
        }

        /* Focus States */
        .btn:focus-visible,
        .slider-btn:focus-visible,
        .indicator:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            .service-card,
            .btn,
            .slider-image {
                animation: none;
                transition: none;
            }

            .service-card:hover,
            .btn:hover {
                transform: none;
            }
        }
    </style>
    @endpush
</x-filament-panels::layout.base>