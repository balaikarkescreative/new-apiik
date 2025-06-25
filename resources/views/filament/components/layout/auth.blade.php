{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/components/layout/auth.blade.php --}}
@php
    use Filament\Support\Enums\MaxWidth;
    use App\Models\Visitor;

    $backgroundUrl = asset('images/auth-bg.jpg');

    // Ambil data visitor untuk halaman login
    $visitorLogin = Visitor::where('halaman', 'halaman utama')->first();
    $totalPengunjungLogin = $visitorLogin->jumlah_pengunjung;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">

    <div class="fi-simple-layout flex min-h-screen flex-col items-center"
                style="background-image: url('{{ $backgroundUrl }}'); background-position: center; background-size: cover; background-repeat: no-repeat; background-attachment: fixed; background-blend-mode: overlay; background-color: rgba(198, 255, 255, 0.453);"
    >
        {{-- Top Bar with Logos --}}
        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <div class="absolute end-0 top-0 flex h-16 items-center gap-x-4 pe-4 md:pe-6 lg:pe-8">
                @if (filament()->hasDatabaseNotifications())
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications()
                    ])
                @endif
            </div>
        @else
            {{-- Show logos even when not authenticated --}}
            <div class="absolute end-0 top-0 flex h-16 items-center gap-x-4 pe-4 md:pe-6 lg:pe-8 my-4">
                {{-- Institutional Logos --}}
                {{-- Additional Institutional Logos --}}
                <div class="flex items-center gap-x-6">
                    <img src="{{ asset('images/logo_anti_korupsi_bkk.png') }}" alt="Logo Kemenkes" class="h-16 w-auto">
                    <img src="{{ asset('images/LOGO_KARANTINA.png') }}" alt="Logo BKK" class="h-16 w-auto">
                </div>
            </div>
        @endif

        <div class="fi-simple-main-ctn flex w-full flex-grow items-center justify-center">
            <main
                @class([
                    'fi-simple-main my-16 w-full bg-white px-6 py-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 sm:rounded-xl sm:px-12',
                    match ($maxWidth ??= (filament()->getSimplePageMaxContentWidth() ?? MaxWidth::Large)) {
                        MaxWidth::ExtraSmall, 'xs' => 'max-w-xs',
                        MaxWidth::Small, 'sm' => 'max-w-sm',
                        MaxWidth::Medium, 'md' => 'max-w-md',
                        MaxWidth::Large, 'lg' => 'max-w-lg',
                        MaxWidth::ExtraLarge, 'xl' => 'max-w-xl',
                        MaxWidth::TwoExtraLarge, '2xl' => 'max-w-2xl',
                        MaxWidth::ThreeExtraLarge, '3xl' => 'max-w-3xl',
                        MaxWidth::FourExtraLarge, '4xl' => 'max-w-4xl',
                        MaxWidth::FiveExtraLarge, '5xl' => 'max-w-5xl',
                        MaxWidth::SixExtraLarge, '6xl' => 'max-w-6xl',
                        MaxWidth::SevenExtraLarge, '7xl' => 'max-w-7xl',
                        MaxWidth::Full, 'full' => 'max-w-full',
                        MaxWidth::MinContent, 'min' => 'max-w-min',
                        MaxWidth::MaxContent, 'max' => 'max-w-max',
                        MaxWidth::FitContent, 'fit' => 'max-w-fit',
                        MaxWidth::Prose, 'prose' => 'max-w-prose',
                        MaxWidth::ScreenSmall, 'screen-sm' => 'max-w-screen-sm',
                        MaxWidth::ScreenMedium, 'screen-md' => 'max-w-screen-md',
                        MaxWidth::ScreenLarge, 'screen-lg' => 'max-w-screen-lg',
                        MaxWidth::ScreenExtraLarge, 'screen-xl' => 'max-w-screen-xl',
                        MaxWidth::ScreenTwoExtraLarge, 'screen-2xl' => 'max-w-screen-2xl',
                        default => $maxWidth,
                    },
                ])
            >
                {{ $slot }}
            </main>
        </div>

        {{-- Video Tutorial Button - Moved higher to avoid covering footer --}}
        <div style="position: fixed; bottom: 80px; left: 24px; z-index: 50;">
            <button
                onclick="openVideoTutorial()"
                aria-label="Video Panduan Penggunaan Aplikasi"
                style="
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    background-color: #dc2626;
                    color: white;
                    padding: 12px 16px;
                    border-radius: 25px;
                    border: none;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                    transition: all 0.3s ease;
                    font-size: 14px;
                    font-weight: 500;
                "
                onmouseover="this.style.backgroundColor='#b91c1c'; this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 20px rgba(220, 38, 38, 0.4)'"
                onmouseout="this.style.backgroundColor='#dc2626'; this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(220, 38, 38, 0.3)'"
            >
                {{-- Play Icon --}}
                <svg style="width: 18px; height: 18px;" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                {{-- Button Text --}}
                <span>Video Panduan Penggunaan Aplikasi</span>
            </button>
        </div>

        @include('filament.pages.auth.wbs')

        {{-- Footer Start --}}
        @include('filament.components.layout.footer')
        {{-- Footer End --}}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire->getRenderHookScopes()) }}
    </div>

    {{-- JavaScript --}}
    <script>
        function openVideoTutorial() {
            // Replace with your actual YouTube video URL
            const youtubeUrl = 'https://www.youtube.com/watch?v=YOUR_VIDEO_ID';
            window.open(youtubeUrl, '_blank', 'noopener,noreferrer');
        }
    </script>

    {{-- Responsive CSS --}}
    <style>
        @media (max-width: 640px) {
            [style*="position: fixed"][style*="bottom: 80px"] {
                bottom: 70px !important;
                left: 16px !important;
            }

            [style*="position: fixed"] button {
                padding: 10px 12px !important;
                font-size: 12px !important;
            }

            [style*="position: fixed"] svg {
                width: 16px !important;
                height: 16px !important;
            }

            /* Mobile responsive for logos */
            .absolute.end-0.top-0 {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            .absolute.end-0.top-0 img {
                height: 24px !important;
            }

            .absolute.end-0.top-0 .flex.items-center.gap-x-2 {
                gap: 4px !important;
            }
        }

        /* Ensure good spacing from WBS button */
        @media (min-width: 641px) {
            [style*="position: fixed"][style*="bottom: 80px"] {
                bottom: 90px !important;
            }
        }

        /* Logo hover effects */
        .absolute.end-0.top-0 img {
            transition: transform 0.2s ease;
        }

        .absolute.end-0.top-0 img:hover {
            transform: scale(1.1);
        }
    </style>
</x-filament-panels::layout.base>
