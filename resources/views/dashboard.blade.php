<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between mb-4">
                        <!-- 期間選択 -->
                        <div class="flex gap-2">
                            <select id="displayType" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="daily" {{ $displayType === 'daily' ? 'selected' : '' }}>日単位</option>
                                <option value="monthly" {{ $displayType === 'monthly' ? 'selected' : '' }}>月単位</option>
                                <option value="yearly" {{ $displayType === 'yearly' ? 'selected' : '' }}>年単位</option>
                                <option value="all" {{ $displayType === 'all' ? 'selected' : '' }}>全期間</option>
                            </select>

                            <select id="year" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 {{ !in_array($displayType, ['yearly', 'monthly', 'daily']) ? 'hidden' : '' }}">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}年</option>
                                @endforeach
                            </select>

                            <select id="month" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 {{ !in_array($displayType, ['monthly', 'daily']) ? 'hidden' : '' }}">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>{{ $m }}月</option>
                                @endforeach
                            </select>

                            <select id="day" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 {{ $displayType !== 'daily' ? 'hidden' : '' }}">
                                @foreach(range(1, 31) as $d)
                                    <option value="{{ $d }}" {{ $d == $currentDay ? 'selected' : '' }}>{{ $d }}日</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- デバイス選択 -->
                        <select id="deviceId" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <option value="all" {{ $selectedDevice === 'all' ? 'selected' : '' }}>全てのデバイス</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" {{ $selectedDevice == $device->id ? 'selected' : '' }}>
                                    {{ $device->device_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- グラフ表示エリア -->
                    <div class="w-full h-96">
                        <canvas id="powerChart"></canvas>
                    </div>

                    <!-- 数値表示エリア -->
                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">総発電量</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                <span id="totalPower">{{ number_format($chartData['total'], 2) }}</span> kWh
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">平均発電量</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                <span id="averagePower">{{ number_format($chartData['average'], 2) }}</span> kWh
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">最大発電量</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                <span id="maxPower">{{ number_format($chartData['max'], 2) }}</span> kWh
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let chart = null;

        // グラフの初期化
        function initChart(data) {
            const ctx = document.getElementById('powerChart').getContext('2d');
            
            if (chart) {
                chart.destroy();
            }

            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: '発電量',
                        data: data.data,
                        borderColor: '#4B5563',
                        backgroundColor: 'rgba(75, 85, 99, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '発電量 (kWh)'
                            }
                        },
                        x: {
                            grid: {
                                display: true
                            },
                            title: {
                                display: true,
                                text: data.unit
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }

        // データの更新
        function updateChart() {
            const params = new URLSearchParams({
                displayType: document.getElementById('displayType').value,
                year: document.getElementById('year').value,
                month: document.getElementById('month').value,
                day: document.getElementById('day').value,
                deviceId: document.getElementById('deviceId').value
            });

            fetch(`/dashboard?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                initChart(data);
                document.getElementById('totalPower').textContent = data.total.toFixed(2);
                document.getElementById('averagePower').textContent = data.average.toFixed(2);
                document.getElementById('maxPower').textContent = data.max.toFixed(2);
            })
            .catch(error => console.error('Error:', error));
        }

        // イベントリスナーの設定
        document.addEventListener('DOMContentLoaded', function() {
            // 初期データでグラフを描画
            initChart(@json($chartData));

            // プルダウンの変更イベントを監視
            ['displayType', 'year', 'month', 'day', 'deviceId'].forEach(id => {
                document.getElementById(id)?.addEventListener('change', updateChart);
            });

            // 表示期間の変更時に関連フィールドの表示/非表示を切り替え
            document.getElementById('displayType').addEventListener('change', function() {
                const type = this.value;
                document.getElementById('year').classList.toggle('hidden', !['yearly', 'monthly', 'daily'].includes(type));
                document.getElementById('month').classList.toggle('hidden', !['monthly', 'daily'].includes(type));
                document.getElementById('day').classList.toggle('hidden', type !== 'daily');
            });
        });
    </script>
    @endpush
</x-app-layout>
