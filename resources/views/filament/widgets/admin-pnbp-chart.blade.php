{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/filament/widgets/admin-pnbp-chart.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📈 Tren Pendapatan PNBP ICV Per Tahun
        </x-slot>

        <div class="space-y-4">
            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-blue-600 dark:text-blue-400 text-sm font-medium">Total Sejak 2023</div>
                    <div class="text-blue-900 dark:text-blue-100 text-2xl font-bold">
                        Rp {{ number_format($this->getViewData()['totalPendapatan'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-green-600 dark:text-green-400 text-sm font-medium">Tarif per ICV</div>
                    <div class="text-green-900 dark:text-green-100 text-2xl font-bold">
                        Rp {{ number_format($this->getViewData()['tarif'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <div class="text-purple-600 dark:text-purple-400 text-sm font-medium">Rata-rata per Tahun</div>
                    <div class="text-purple-900 dark:text-purple-100 text-2xl font-bold">
                        @if(count($this->getViewData()['chartData']) > 0)
                            Rp {{ number_format($this->getViewData()['totalPendapatan'] / count($this->getViewData()['chartData']), 0, ',', '.') }}
                        @else
                            Rp 0
                        @endif
                    </div>
                </div>
            </div>

            <!-- Debug Info -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Debug Info:</strong>
                    Data Count: {{ count($this->getViewData()['chartData']) }} |
                    @if(count($this->getViewData()['chartData']) > 0)
                        Sample: {{ $this->getViewData()['chartData'][0]['year'] ?? 'N/A' }} -
                        {{ number_format($this->getViewData()['chartData'][0]['pendapatan_juta'] ?? 0, 2) }}M
                    @endif
                </p>
            </div>

            <!-- Visual Chart dengan Canvas yang lebih sederhana -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-medium mb-4">Grafik Tren Pendapatan</h4>

                @if(count($this->getViewData()['chartData']) > 0)
                    <div class="relative">
                        <canvas id="pnbpChart" width="800" height="400"></canvas>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p>Belum ada data PNBP untuk ditampilkan</p>
                    </div>
                @endif
            </div>

            <!-- Chart Data Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Tahun</th>
                            <th class="text-right py-3 px-4 font-medium text-gray-700 dark:text-gray-300">ICV dengan SBBK</th>
                            <th class="text-right py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Pendapatan (Juta Rp)</th>
                            <th class="text-right py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->getViewData()['chartData'] as $data)
                            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-3 px-4 font-medium">{{ $data['year'] }}</td>
                                <td class="py-3 px-4 text-right">{{ number_format($data['icv_count']) }} unit</td>
                                <td class="py-3 px-4 text-right font-medium text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($data['pendapatan_juta'], 2) }}M
                                </td>
                                <td class="py-3 px-4 text-right">
                                    Rp {{ number_format($data['pendapatan'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data PNBP untuk ditampilkan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Script dengan error handling lebih baik -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Tunggu sampai halaman selesai load
            window.addEventListener('load', function() {
                console.log('Page loaded, initializing chart...');

                const canvas = document.getElementById('pnbpChart');
                if (!canvas) {
                    console.error('Canvas not found');
                    return;
                }

                const chartData = @json($this->getViewData()['chartData']);
                console.log('Chart data from PHP:', chartData);

                // Check if Chart.js is loaded
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js not loaded');
                    return;
                }

                if (!chartData || chartData.length === 0) {
                    console.log('No data available for chart');
                    return;
                }

                // Prepare data
                const labels = chartData.map(item => item.year.toString());
                const data = chartData.map(item => parseFloat(item.pendapatan_juta) || 0);

                console.log('Labels:', labels);
                console.log('Data:', data);

                // Create chart
                try {
                    const ctx = canvas.getContext('2d');

                    const chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Pendapatan PNBP (Juta Rp)',
                                data: data,
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: 'rgb(59, 130, 246)',
                                pointBorderColor: 'rgb(255, 255, 255)',
                                pointBorderWidth: 2,
                                pointRadius: 6,
                                pointHoverRadius: 8,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            return `Tahun ${context[0].label}`;
                                        },
                                        label: function(context) {
                                            const index = context.dataIndex;
                                            const item = chartData[index];
                                            return [
                                                `Pendapatan: Rp ${parseFloat(item.pendapatan_juta).toFixed(2)} Juta`,
                                                `ICV dengan SBBK: ${parseInt(item.icv_count).toLocaleString()} unit`,
                                                `Total: Rp ${parseInt(item.pendapatan).toLocaleString()}`
                                            ];
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Pendapatan (Juta Rupiah)'
                                    },
                                    ticks: {
                                        callback: function(value) {
                                            return 'Rp ' + value.toFixed(1) + 'M';
                                        }
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Tahun'
                                    }
                                }
                            }
                        }
                    });

                    console.log('Chart created successfully');

                } catch (error) {
                    console.error('Error creating chart:', error);
                }
            });
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
