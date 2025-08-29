{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/components/layout/video-modal.blade.php --}}

<x-filament::modal
    id="video-panduan-modal"
    width="6xl"
    :close-by-clicking-away="true"
>
    <x-slot name="heading">
        <div class="modal-header">
            <div class="modal-icon">
                <x-filament::icon
                    icon="heroicon-o-play-circle"
                    class="icon-play"
                />
            </div>
            <div class="modal-title-group">
                <h3 class="modal-title">Video Panduan Penggunaan Aplikasi</h3>
                <p class="modal-subtitle">Tutorial lengkap cara menggunakan aplikasi APIIK</p>
            </div>
        </div>
    </x-slot>

    <div class="modal-content">
        {{-- Video Container --}}
        <div class="video-container">
            <div class="video-wrapper">
                <iframe
                    src="https://drive.google.com/file/d/1BcLJdPSmhLV9GIHBI0CjP8Wd8_6grDIi/preview"
                    class="video-iframe"
                    allow="autoplay; encrypted-media"
                    allowfullscreen
                    title="Video Panduan Penggunaan Aplikasi ICV"
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</x-filament::modal>

<x-filament::modal
    id="video-tutorial-modal"
    width="6xl"
    :close-by-clicking-away="true"
>
    <x-slot name="heading">
        <div class="modal-header">
            <div class="modal-icon">
                <x-filament::icon
                    icon="heroicon-o-play-circle"
                    class="icon-play"
                />
            </div>
            <div class="modal-title-group">
                <h3 class="modal-title">Video Tutorial Aplikasi</h3>
            </div>
        </div>
    </x-slot>

    <div class="modal-content">
        {{-- Video Container --}}
        <div class="video-container">
            <div class="video-wrapper">
                <iframe
                    src="https://drive.google.com/file/d/1mSkCFFLEyUPHqerDzyBVMv-2JZMSVP_p/preview"
                    class="video-iframe"
                    allow="autoplay; encrypted-media"
                    allowfullscreen
                    title="Video Panduan Penggunaan Aplikasi ICV"
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</x-filament::modal>

@push('styles')
<style>
    /* Modal Header */
    .modal-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0;
    }

    .modal-icon {
        padding: 0.5rem;
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border-radius: 0.75rem;
        flex-shrink: 0;
    }

    .icon-play {
        height: 1.5rem;
        width: 1.5rem;
        color: #dc2626;
    }

    .modal-title-group {
        flex: 1;
        min-width: 0;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
        margin: 0 0 0.25rem 0;
        line-height: 1.3;
    }

    .modal-subtitle {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0;
        line-height: 1.4;
    }

    /* Modal Content */
    .modal-content {
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Video Container */
    .video-container {
        width: 100%;
        background: #f9fafb;
        border-radius: 0.75rem;
        padding: 0.5rem;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
    }

    .video-wrapper {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #000;
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .video-iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
        border-radius: 0.5rem;
    }

    /* Video Info */
    .video-info {
        padding: 1rem;
        background: #f8fafc;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        background: white;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .info-item:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .info-icon {
        width: 1rem;
        height: 1rem;
        color: #6b7280;
        flex-shrink: 0;
    }

    .info-text {
        font-size: 0.875rem;
        color: #374151;
        font-weight: 500;
    }

    /* Dark Mode */
    .dark .modal-icon {
        background: linear-gradient(135deg, #7f1d1d, #991b1b);
    }

    .dark .icon-play {
        color: #fca5a5;
    }

    .dark .modal-title {
        color: #f9fafb;
    }

    .dark .modal-subtitle {
        color: #d1d5db;
    }

    .dark .video-container {
        background: #374151;
    }

    .dark .video-info {
        background: #1f2937;
        border-color: #374151;
    }

    .dark .info-item {
        background: #374151;
        border-color: #4b5563;
    }

    .dark .info-item:hover {
        background: #4b5563;
        border-color: #6b7280;
    }

    .dark .info-icon {
        color: #9ca3af;
    }

    .dark .info-text {
        color: #e5e7eb;
    }

    /* Responsive Design */
    @media (min-width: 640px) {
        .modal-icon {
            padding: 0.75rem;
        }

        .icon-play {
            height: 1.75rem;
            width: 1.75rem;
        }

        .modal-title {
            font-size: 1.375rem;
        }

        .modal-subtitle {
            font-size: 1rem;
        }

        .modal-content {
            gap: 2rem;
        }

        .video-container {
            padding: 0.75rem;
        }

        .info-grid {
            gap: 1.5rem;
        }
    }

    @media (min-width: 768px) {
        .info-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    /* Mobile Optimization */
    @media (max-width: 768px) {
        /* Modal takes more screen space on mobile */
        [id="video-tutorial-modal"] {
            width: 95vw !important;
            max-width: 95vw !important;
            margin: 1rem;
        }

        .modal-content {
            padding: 0.5rem;
            gap: 1rem;
        }

        .video-container {
            padding: 0.25rem;
        }

        .video-info {
            padding: 0.75rem;
        }

        .info-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .info-item {
            padding: 0.75rem;
        }

        /* Stack header vertically on very small screens */
        .modal-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .modal-title {
            font-size: 1.125rem;
        }

        .modal-subtitle {
            font-size: 0.8125rem;
        }
    }

    @media (max-width: 375px) {
        [id="video-tutorial-modal"] {
            width: 98vw !important;
            max-width: 98vw !important;
            margin: 0.5rem;
        }

        .modal-content {
            padding: 0.25rem;
        }

        .info-text {
            font-size: 0.8125rem;
        }
    }

    /* Loading State */
    .video-wrapper::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 3rem;
        height: 3rem;
        border: 3px solid #e5e7eb;
        border-top-color: #dc2626;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        z-index: 1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .video-iframe:not([src]) + .video-wrapper::before {
        opacity: 1;
    }

    @keyframes spin {
        to {
            transform: translate(-50%, -50%) rotate(360deg);
        }
    }

    /* Focus States */
    .info-item:focus-visible {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }

    /* High Contrast Mode */
    @media (prefers-contrast: high) {
        .video-container,
        .video-info,
        .info-item {
            border: 2px solid currentColor;
        }
    }

    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .info-item {
            transition: none;
        }

        .video-wrapper::before {
            animation: none;
        }
    }
</style>
@endpush