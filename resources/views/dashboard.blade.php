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

                    <!-- CO2削減量と電気代削減量 -->
                    <div class="mt-4 flex justify-between gap-4">
                        <div class="flex-1 p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                            <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">総CO2削減量</h3>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                                <span id="co2Reduction">{{ number_format($co2Reduction, 2) }}</span> kg-CO2
                            </p>
                            <p class="text-sm text-green-700 dark:text-green-300">※ 1kWhあたり0.472kg-CO2として計算</p>
                        </div>
                        <div class="flex-1 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">総電気代削減量</h3>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                <span id="electricityCost">{{ number_format($electricityCost, 0) }}</span> 円
                            </p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">※ 1kWhあたり27円として計算</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // APIのURLを定数として定義（サブディレクトリを考慮）
        const CHART_API_URL = "{{ url('/balcony-energy/chart-data') }}";
        let chart = null;

        // グラフの初期化
        function initChart(data) {
            const ctx = document.getElementById('powerChart').getContext('2d');
            
            if (chart) {
                chart.destroy();
            }

            // 日単位表示の場合のオプション
            const isDaily = document.getElementById('displayType').value === 'daily';
            const scales = {
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
                    },
                    // 日単位の場合は追加の設定
                    ticks: isDaily ? {
                        maxRotation: 45,
                        minRotation: 45
                    } : {}
                }
            };

            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: '発電量',
                        data: data.data,
                        borderColor: data.color,
                        backgroundColor: `${data.color}33`,
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            align: 'start',
                            labels: {
                                boxWidth: 15,
                                padding: 15
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: data.color + 'CC',
                        }
                    },
                    scales: scales,
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
                device: document.getElementById('deviceId').value // deviceIdをdeviceに変更
            });

            // 新しいAPIエンドポイントを使用
            fetch(`${CHART_API_URL}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // データ構造を変換
                const chartData = {
                    labels: data.chartData.labels,
                    data: data.chartData.datasets[0].data,
                    color: data.chartData.datasets[0].borderColor,
                    unit: data.unit
                };
                
                initChart(chartData);
                document.getElementById('totalPower').textContent = data.stats.total.toFixed(2);
                document.getElementById('averagePower').textContent = data.stats.average.toFixed(2);
                document.getElementById('maxPower').textContent = data.stats.max.toFixed(2);
                document.getElementById('co2Reduction').textContent = data.co2Reduction.toFixed(2);
                document.getElementById('electricityCost').textContent = Math.round(data.electricityCost).toLocaleString();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('データの更新中にエラーが発生しました。');
            });
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
