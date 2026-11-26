{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/components/layout/header.blade.php --}}

<header class="header-section absolute inset-x-0 top-0 z-30 pt-4">
    <div class="header-container max-w-8xl mx-auto px-4 lg:px-8">
        
        {{-- Authenticated State --}}
        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <div class="header-content flex items-center justify-between">
                {{-- Logo Left --}}
                <div class="header-logo">
                    <img src="{{ asset('storage/assets/site_logo.png') }}"
                         alt="Logo Kemenkes"
                         class="logo-img">
                </div>

                {{-- Notifications Right --}}
                <div class="header-notifications">
                    @if (filament()->hasDatabaseNotifications())
                        @livewire(Filament\Livewire\DatabaseNotifications::class, [
                            'lazy' => filament()->hasLazyLoadedDatabaseNotifications()
                        ])
                    @endif
                </div>
            </div>
        @else
            {{-- Non-authenticated State --}}
            <div class="header-content flex items-center justify-between">
                {{-- Logo Left --}}
                <div class="header-logo">
                    <img src="{{ asset('storage/assets/site_logo.png') }}"
                         alt="Logo Kemenkes"
                         class="logo-img">
                </div>

                {{-- Institutional Logos Right --}}
                <div class="header-institutions">
                    <div class="institutions-group">
                        <img src="{{ asset('images/logo_anti_korupsi_bkk.png') }}"
                             alt="Logo Anti Korupsi"
                             class="institution-logo">
                        <img src="{{ asset('images/LOGO_KARANTINA.png') }}"
                             alt="Logo Karantina"
                             class="institution-logo">
                    </div>
                </div>
            </div>
        @endif
    </div>
</header>

@push('styles')
<style>
    /* Header Structure */
    .header-section {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 30;
        padding-top: 1rem;
    }

    .header-container {
        /* span full width so logos can be flush to viewport edges */
        max-width: none;
        width: 100%;
        margin: 0;
        padding-left: 0;
        padding-right: 0;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 4rem;
    }

    /* Logo Styles */
    .header-logo {
        flex-shrink: 0;
        display: flex;
        align-items: center;
    }

    /* ensure logo wrappers sit at the absolute left/right edges */
    .header-logo { padding-left: 0; }
    .header-institutions { padding-right: 0; }

    .logo-img {
        height: 2.5rem;
        width: auto;
        transition: transform 0.3s ease;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }

    .logo-img:hover {
        transform: scale(1.05);
    }

    /* Notifications */
    .header-notifications {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    /* Institutional Logos */
    .header-institutions {
        flex-shrink: 0;
        display: flex;
        align-items: center;
    }

    .institutions-group {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .institution-logo {
        height: 2.5rem;
        width: auto;
        transition: transform 0.3s ease;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }

    .institution-logo:hover {
        transform: scale(1.05);
    }

    /* Dark Mode */
    .dark .logo-img,
    .dark .institution-logo {
        filter: brightness(1.1) contrast(0.9) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }

    /* Responsive Design */
    @media (min-width: 640px) {
        .logo-img,
        .institution-logo {
            height: 3rem;
        }

        .institutions-group {
            gap: 1.5rem;
        }
    }

    @media (min-width: 768px) {
        .logo-img,
        .institution-logo {
            height: 3.5rem;
        }

        .header-container {
            padding-left: 2rem;
            padding-right: 2rem;
        }
    }

    @media (min-width: 1024px) {
        .logo-img {
            height: 4rem;
        }

        .institution-logo {
            height: 4rem;
        }

        .institutions-group {
            gap: 2rem;
        }

        .header-container {
            padding-left: 3rem;
            padding-right: 3rem;
        }
    }

    /* Mobile Optimization */
    @media (max-width: 640px) {
        .header-content {
            min-height: 3rem;
        }

        .institutions-group {
            gap: 0.5rem;
        }

        .institution-logo {
            height: 2rem;
        }

        .logo-img {
            height: 2rem;
        }

        /* Prevent overlap on small screens: allow logos to shrink while staying edge-aligned */
        .header-logo {
            max-width: none;
            padding-left: 0.5rem;
        }

        .header-institutions {
            max-width: none;
            padding-right: 0.5rem;
        }
    }

    @media (max-width: 375px) {
        .header-logo {
            max-width: 40%;
        }

        .header-institutions {
            max-width: 55%;
        }

        .institution-logo,
        .logo-img {
            height: 1.75rem;
        }

        .institutions-group {
            gap: 0.25rem;
            overflow: hidden;
        }
    }

    /* Focus States */
    .logo-img:focus-visible,
    .institution-logo:focus-visible {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
        border-radius: 0.25rem;
    }

    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .logo-img,
        .institution-logo {
            transition: none;
        }

        .logo-img:hover,
        .institution-logo:hover {
            transform: none;
        }
    }
</style>
@endpush