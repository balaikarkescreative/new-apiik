{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/components/layout/floating-elements.blade.php --}}

{{-- Video Tutorial Floating Button --}}
<div class="floating-tutorial fixed bottom-20 left-4 lg:left-6 z-40">
    <button x-data="{ modalOpen: false }"
            @open-modal.window="modalOpen = $event.detail.id === 'video-panduan-modal'"
            @close-modal.window="modalOpen = false"
            @click="$dispatch('open-modal', { id: 'video-panduan-modal' })"
            class="tutorial-btn"
            :class="{ 'hidden': modalOpen }"
            aria-label="Video Panduan Penggunaan Aplikasi">
        
        <x-filament::icon icon="heroicon-s-play" class="play-icon" />
        <span class="tutorial-text" aria-hidden="false">
            <span class="text-full"><span class="marquee">Video Panduan Penggunaan Aplikasi</span></span>
            <span class="text-short">Video Panduan</span>
        </span>
    </button>
</div>

@push('styles')
<style>
    /* Floating Tutorial Button */
    .floating-tutorial {
        position: fixed;
        bottom: 5rem;
        left: 1rem;
        z-index: 40;
    }

    .tutorial-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 9999px;
        border: none;
        cursor: pointer;
        box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.3);
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 280px;
        white-space: nowrap;
    }

    .tutorial-btn:hover {
        background: linear-gradient(135deg, #b91c1c, #991b1b);
        transform: scale(1.05);
        box-shadow: 0 20px 25px -5px rgba(220, 38, 38, 0.4);
    }

    .tutorial-btn.hidden {
        opacity: 0;
        pointer-events: none;
        transform: scale(0.95);
    }

    .play-icon {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
    }

    .tutorial-text {
        display: inline-block;
        overflow: hidden;
        vertical-align: middle;
        white-space: nowrap;
        max-width: 12rem; /* constrains the visible area for the running text */
    }

    /* Running text (marquee) */
    .marquee {
        display: inline-block;
        padding-left: 100%;
        animation: marquee 10s linear infinite;
    }

    /* Pause on hover/focus for readability */
    .tutorial-btn:hover .marquee,
    .tutorial-btn:focus-within .marquee,
    .tutorial-btn:active .marquee {
        animation-play-state: paused;
    }

    @keyframes marquee {
        from { transform: translateX(0%); }
        to { transform: translateX(-100%); }
    }

    /* Respect user's reduced motion preference */
    @media (prefers-reduced-motion: reduce) {
        .marquee {
            animation: none;
        }
    }

    .text-full {
        display: inline;
    }

    .text-short {
        display: none;
    }

    /* Dark Mode */
    .dark .tutorial-btn {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.4);
    }

    .dark .tutorial-btn:hover {
        background: linear-gradient(135deg, #991b1b, #7f1d1d);
        box-shadow: 0 20px 25px -5px rgba(220, 38, 38, 0.5);
    }

    /* Responsive Design */
    @media (min-width: 640px) {
        .tutorial-btn {
            padding: 0.875rem 1.25rem;
            font-size: 1rem;
        }

        .play-icon {
            width: 1.5rem;
            height: 1.5rem;
        }

        .floating-tutorial {
            left: 1.5rem;
        }
    }

    @media (min-width: 1024px) {
        .floating-tutorial {
            left: 2rem;
        }

        .tutorial-btn {
            padding: 1rem 1.5rem;
            gap: 0.75rem;
        }
    }

    /* Mobile Optimization */
    @media (max-width: 640px) {
        .floating-tutorial {
            bottom: 4rem;
            left: 0.5rem;
        }

        .tutorial-btn {
            padding: 0.625rem 0.875rem;
            font-size: 0.75rem;
            max-width: 200px;
        }

        .play-icon {
            width: 1rem;
            height: 1rem;
        }

        .text-full {
            display: none;
        }

        .text-short {
            display: inline;
        }
    }

    @media (max-width: 375px) {
        .tutorial-btn {
            padding: 0.5rem 0.75rem;
            max-width: 180px;
            font-size: 0.625rem;
        }

        .text-short {
            display: none;
        }

        .tutorial-text::after {
            content: "Tutorial";
        }
    }

    /* Focus States */
    .tutorial-btn:focus-visible {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }

    /* High Contrast Mode */
    @media (prefers-contrast: high) {
        .tutorial-btn {
            border: 2px solid currentColor;
        }
    }

    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .tutorial-btn {
            transition: none;
        }

        .tutorial-btn:hover {
            transform: none;
        }
    }

    /* Touch Devices */
    @media (hover: none) {
        .tutorial-btn:hover {
            transform: none;
        }

        .tutorial-btn:active {
            transform: scale(0.95);
        }
    }
</style>
@endpush