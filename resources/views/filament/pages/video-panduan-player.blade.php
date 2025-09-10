@php
    $activeVideoUrl = $activeVideo?->video_url;
    $activeVideoTitle = $activeVideo?->title ?? 'Tidak ada video yang dipilih';
    $activeVideoDescription = $activeVideo?->description ?? '';
@endphp

<x-filament-panels::page>
    <!-- CSS untuk Plyr -->
    <style>
        /* Kustomisasi warna utama Plyr */
        :root {
            --plyr-color-main: rgb(var(--primary-600));
            --plyr-video-control-color: #fff;
            --plyr-menu-background: rgba(255, 255, 255, 0.95);
        }
        
        .video-list-item {
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 0.5rem;
        }
        
        .video-list-item:hover {
            background-color: rgb(var(--primary-50));
        }
        
        .dark .video-list-item:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .video-list-item.active {
            background-color: rgb(var(--primary-100));
            border-left: 4px solid rgb(var(--primary-600));
        }
        
        .dark .video-list-item.active {
            background-color: rgba(59, 130, 246, 0.2);
            border-left: 4px solid rgb(59, 130, 246);
        }
        
        .video-list-container {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .video-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100%;
            min-height: 400px;
            background-color: rgb(17, 24, 39);
            color: white;
            border-radius: 0.5rem;
        }
        
        /* Untuk membuat layout 2/3 player dan 1/3 list */
        @media (min-width: 768px) {
            .video-container {
                width: calc(100% - 320px);
            }
            
            .list-container {
                width: 320px;
            }
        }
        /* Loading overlay styles */
        .video-loading-overlay {
            backdrop-filter: blur(2px);
        }

        .video-loading-overlay .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Autoplay message styles */
        .autoplay-message {
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Improve video player responsiveness */
        #player-wrapper {
            position: relative;
            min-height: 200px;
        }
    </style>
    
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 flex flex-col space-y-4">
            @if($activeVideo)
                <div id="player-wrapper" class="w-full overflow-hidden bg-black rounded-lg aspect-video">
                    <video 
                        id="player" 
                        playsinline 
                        controls 
                        class="w-full"
                        preload="metadata"
                        crossorigin
                    >
                        <source src="{{ $activeVideoUrl }}" type="video/mp4">
                        <p>Browser Anda tidak mendukung pemutaran video HTML5.</p>
                    </video>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold">{{ $activeVideoTitle }}</h3>
                    @if($activeVideoDescription)
                        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $activeVideoDescription }}</p>
                    @endif
                    
                    <div class="mt-4 flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                        <span>Ukuran: {{ $activeVideo->video_size ?? 'Tidak diketahui' }}</span>
                        <span>Dibuat: {{ $activeVideo->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            @else
                <div class="video-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14v-4zm-6 5v4m-5-9h12.172a2 2 0 012.028 2H3a2 2 0 012-2z" />
                    </svg>
                    <p class="text-xl font-medium">Tidak ada video yang dipilih</p>
                    <p class="text-sm text-gray-400 mt-2">Pilih video dari daftar di samping</p>
                </div>
            @endif
        </div>
        
        <div class="md:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Video</h3>
                </div>
                
                <div class="video-list-container">
                    @if($videos && $videos->count() > 0)
                        @foreach($videos as $video)
                            <div 
                                wire:key="video-{{ $video->id }}" 
                                wire:click="selectVideo({{ $video->id }})"
                                class="video-list-item p-4 border-b border-gray-100 dark:border-gray-700 {{ $activeVideoId == $video->id ? 'active' : '' }}"
                            >
                                <div class="flex">
                                    <div class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 rounded-lg w-20 h-12 flex items-center justify-center overflow-hidden">
                                        @if($video->video_path)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-1">{{ $video->title }}</h4>
                                        @if($video->description)
                                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">{{ $video->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="p-4 text-center text-gray-500 dark:text-gray-300">
                            <p>Tidak ada video FAQ tersedia.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script untuk Plyr (versi terbaru) -->
    <script src="https://cdn.plyr.io/3.8.3/plyr.polyfilled.js"></script>
    <link rel="stylesheet" href="https://cdn.plyr.io/3.8.3/plyr.css" />
    <script>
        document.addEventListener('livewire:initialized', () => {
            let player = null;

            function initPlayer() {
                if (player) {
                    player.destroy();
                }

                const video = document.getElementById('player');
                if (!video) return;

                // Reset muted dan currentTime sebelum init
                video.currentTime = 0;

                player = new Plyr('#player', {
                    autoplay: true,
                    // muted: true, // pastikan muted
                    i18n: {
                        restart: 'Mulai Ulang',
                        play: 'Putar',
                        pause: 'Jeda',
                        fastForward: 'Maju 10 detik',
                        rewind: 'Mundur 10 detik',
                        enterFullscreen: 'Layar Penuh',
                        exitFullscreen: 'Keluar Layar Penuh',
                    },
                    seekTime: 10,
                    controls: ['play-large', 'restart', 'rewind', 'play', 'fast-forward',
                            'progress', 'current-time', 'duration', 'mute', 'volume', 'fullscreen'],
                    tooltips: { controls: true },
                    clickToPlay: true,
                });

                // Tunggu video benar-benar bisa diputar
                video.addEventListener('canplaythrough', () => {
                    if (player && player.autoplay) {
                        player.play().catch(e => {
                            console.warn('Autoplay gagal:', e.message);
                            // Minta user klik jika autoplay diblokir
                        });
                    }
                }, { once: true });

                // Error handling
                player.on('error', (e) => {
                    console.error('Plyr error:', e.detail.error);
                });

                // Debug
                player.on('loadedmetadata', () => {
                    console.log('Metadata dimuat, durasi:', player.duration);
                });

                player.on('playing', () => {
                    console.log('Video sedang diputar');
                });
            }

            // Inisialisasi pertama kali
            initPlayer();

            // Re-init saat video berubah
            Livewire.on('videoChanged', () => {
                // Beri waktu ekstra untuk DOM update dan video load
                setTimeout(() => {
                    const video = document.getElementById('player');
                    if (video && video.readyState < 1) {
                        // Tunggu video benar-benar dimuat
                        video.addEventListener('loadedmetadata', initPlayer, { once: true });
                    } else {
                        initPlayer();
                    }
                }, 500); // Delay lebih aman
            });
        });
    </script>
</x-filament-panels::page>
