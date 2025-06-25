<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-map class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                <span>Peta Sebaran Pengguna</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Menampilkan lokasi pengguna yang terdaftar di sistem
        </x-slot>

        {{-- <x-slot name="headerActions">
            <div class="flex items-center gap-2">
                @foreach($this->getCachedActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </x-slot> --}}

        <div class="relative">
            <!-- Statistics Cards -->
            <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-950/50 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Pengguna</p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                {{ $this->getTotalUsers() }}
                            </p>
                        </div>
                        <x-heroicon-o-users class="h-8 w-8 text-blue-400" />
                    </div>
                </div>

                <div class="bg-green-50 dark:bg-green-950/50 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">Dengan Lokasi</p>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                                {{ $this->getUsersWithLocation() }}
                            </p>
                        </div>
                        <x-heroicon-o-map-pin class="h-8 w-8 text-green-400" />
                    </div>
                </div>

                <div class="bg-orange-50 dark:bg-orange-950/50 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-orange-600 dark:text-orange-400">Tanpa Lokasi</p>
                            <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                                {{ $this->getUsersWithoutLocation() }}
                            </p>
                        </div>
                        <x-heroicon-o-exclamation-triangle class="h-8 w-8 text-orange-400" />
                    </div>
                </div>
            </div>

            <!-- Map Container -->
            <div class="relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                @if($this->getMarkers())
                    <div
                        class="w-full h-96"
                        x-data="mapWidget"
                        x-init="initMap"
                        wire:ignore
                    >
                        <div
                            id="map-{{ $this->getId() }}"
                            class="w-full h-full"
                            data-markers="{{ json_encode($this->getMarkers()) }}"
                            data-config="{{ json_encode($this->getMapConfig()) }}"
                        ></div>
                    </div>
                @else
                    <div class="flex items-center justify-center h-96 bg-gray-50 dark:bg-gray-800">
                        <div class="text-center">
                            <x-heroicon-o-map class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Tidak ada data lokasi</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada pengguna dengan data lokasi yang tersedia.</p>
                        </div>
                    </div>
                @endif

                <!-- Map Legend -->
                @if($this->getMarkers())
                    <div class="absolute bottom-4 left-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3">
                        <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Keterangan</h4>
                        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                            <div class="w-3 h-3 bg-blue-500 rounded-full border-2 border-white shadow-sm"></div>
                            <span>Lokasi Pengguna</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Info Panel -->
            <div class="mt-4 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" />
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">Informasi Peta</p>
                        <ul class="space-y-1 text-xs">
                            <li>• Klik marker untuk melihat detail pengguna</li>
                            <li>• Gunakan kontrol zoom untuk memperbesar/memperkecil tampilan</li>
                            <li>• Data lokasi berdasarkan koordinat latitude dan longitude pengguna</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mapWidget', () => ({
                map: null,

                initMap() {
                    const mapElement = document.getElementById('map-{{ $this->getId() }}');
                    const markers = JSON.parse(mapElement.dataset.markers || '[]');
                    const config = JSON.parse(mapElement.dataset.config || '{}');

                    // Initialize Leaflet map (assuming you're using Leaflet)
                    this.map = L.map('map-{{ $this->getId() }}', {
                        center: config.center || [-2.5489, 118.0149], // Indonesia center
                        zoom: config.zoom || 5,
                        zoomControl: true,
                        scrollWheelZoom: true
                    });

                    // Add tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(this.map);

                    // Add markers
                    if (markers.length > 0) {
                        const markersGroup = L.featureGroup();

                        markers.forEach(marker => {
                            const leafletMarker = L.marker([marker.lat, marker.lng])
                                .bindPopup(`
                                    <div class="p-2">
                                        <h3 class="font-semibold text-gray-900">${marker.popup}</h3>
                                        <p class="text-sm text-gray-600">Lat: ${marker.lat}</p>
                                        <p class="text-sm text-gray-600">Lng: ${marker.lng}</p>
                                    </div>
                                `);

                            markersGroup.addLayer(leafletMarker);
                        });

                        markersGroup.addTo(this.map);

                        // Fit map to markers bounds
                        if (markers.length > 1) {
                            this.map.fitBounds(markersGroup.getBounds(), { padding: [20, 20] });
                        }
                    }
                }
            }));
        });
    </script>
    @endpush

    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
</x-filament-widgets::widget>
