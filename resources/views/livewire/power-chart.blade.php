<div class="p-6">
    <div class="flex justify-between mb-4">
        <!-- 期間選択 -->
        <div class="flex gap-2">
            <select wire:model="displayType" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                <option value="daily">日単位</option>
                <option value="monthly">月単位</option>
                <option value="yearly">年単位</option>
                <option value="all">全期間</option>
            </select>

            @if($displayType === 'yearly' || $displayType === 'monthly' || $displayType === 'daily')
            <select wire:model="selectedYear" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach($years as $year)
                    <option value="{{ $year }}">{{ $year }}年</option>
                @endforeach
            </select>
            @endif

            @if($displayType === 'monthly' || $displayType === 'daily')
            <select wire:model="selectedMonth" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach(range(1, 12) as $month)
                    <option value="{{ $month }}">{{ $month }}月</option>
                @endforeach
            </select>
            @endif

            @if($displayType === 'daily')
            <select wire:model="selectedDay" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach(range(1, $daysInMonth) as $day)
                    <option value="{{ $day }}">{{ $day }}日</option>
                @endforeach
            </select>
            @endif
        </div>

        <!-- デバイス選択 -->
        <select wire:model="selectedDevice" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            <option value="all">全てのデバイス</option>
            @foreach($devices as $device)
                <option value="{{ $device->id }}">{{ $device->device_name }}</option>
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
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalPower, 2) }} kWh</p>
        </div>
        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">平均発電量</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($averagePower, 2) }} kWh</p>
        </div>
        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">最大発電量</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($maxPower, 2) }} kWh</p>
        </div>
    </div>

    <!-- デバッグ情報表示エリア -->
    <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">デバッグ情報</h3>
        <div class="overflow-x-auto">
            <h4 class="font-medium">ラベル ({{ count($data['labels'] ?? []) }}件):</h4>
            <pre class="text-xs">{{ json_encode($data['labels'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            
            <h4 class="font-medium mt-2">データ ({{ count($data['power'] ?? []) }}件):</h4>
            <pre class="text-xs">{{ json_encode($data['power'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    @push('scripts')
    <script>
        // ページ読み込み完了時に実行
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            const ctx = document.getElementById('powerChart');
            
            if (!ctx) {
                console.error('Canvas element not found!');
                return;
            }
            
            console.log('Canvas element found:', ctx);
            const context2d = ctx.getContext('2d');
            console.log('2D Context:', context2d);
            
            let chart = null;
            
            // updateChartイベントのリスナー
            window.addEventListener('updateChart', event => {
                console.log('Chart update event received!');
                console.log('Event detail:', JSON.stringify(event.detail, null, 2));
                
                // データ構造を確認
                const chartData = event.detail.chartData || event.detail;
                console.log('Chart data structure:', chartData);
                console.log('Labels:', chartData.labels);
                console.log('Datasets:', chartData.datasets);
                
                if (!chartData.labels || !chartData.datasets) {
                    console.error('Invalid chart data structure!');
                    return;
                }
                
                if (chart) {
                    console.log('Destroying existing chart');
                    chart.destroy();
                }
                
                try {
                    console.log('Creating new chart with data:', {
                        labels: chartData.labels,
                        datasets: chartData.datasets
                    });
                    
                    chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: chartData.datasets
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
                                        text: '期間'
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
                    console.log('Chart created successfully:', chart);
                } catch (error) {
                    console.error('Error creating chart:', error);
                    console.error('Error stack:', error.stack);
                }
            });
        });
    </script>
    @endpush
</div> 