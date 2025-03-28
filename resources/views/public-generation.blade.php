<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('みんなの発電') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- 期間選択 -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">期間</label>
                            <select id="periodType" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="all">全期間</option>
                                <option value="yearly">年単位</option>
                                <option value="monthly">月単位</option>
                                <option value="daily">日単位</option>
                            </select>

                            <div id="periodSelectors" class="space-y-2">
                                <select id="year" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 hidden">
                                    @foreach($years as $y)
                                        <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}年</option>
                                    @endforeach
                                </select>

                                <select id="month" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 hidden">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>{{ $m }}月</option>
                                    @endforeach
                                </select>

                                <select id="day" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 hidden">
                                    @foreach(range(1, 31) as $d)
                                        <option value="{{ $d }}" {{ $d == $currentDay ? 'selected' : '' }}>{{ $d }}日</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- 地域選択 -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">地域</label>
                            <select id="regionType" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="all">日本全国</option>
                                <option value="prefecture">都道府県</option>
                                <option value="city">市町村</option>
                            </select>

                            <div id="regionSelectors" class="space-y-2">
                                <select id="prefecture" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 hidden">
                                    @foreach($prefectures as $prefecture)
                                        <option value="{{ $prefecture->id }}">{{ $prefecture->name }}</option>
                                    @endforeach
                                </select>

                                <select id="city" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 hidden">
                                    <option value="">市町村を選択</option>
                                </select>
                            </div>
                        </div>

                        <!-- 機器選択 -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">機器</label>
                            <select id="deviceType" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="all">全ての機器</option>
                                <option value="manufacturer">メーカー</option>
                                <option value="model">型番</option>
                            </select>

                            <div id="deviceSelectors" class="space-y-2">
                                <select id="manufacturer" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 hidden">
                                    @foreach($manufacturers as $manufacturer)
                                        <option value="{{ $manufacturer->manufacturer }}">{{ $manufacturer->manufacturer }}</option>
                                    @endforeach
                                </select>

                                <select id="modelNumber" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 hidden">
                                    @foreach($models as $model)
                                        <option value="{{ $model->model_number }}">{{ $model->model_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- グラフ表示エリア -->
                    <div class="w-full h-96">
                        <canvas id="powerChart"></canvas>
                    </div>

                    <!-- 集計値表示エリア -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">総発電量</h3>
                            <p class="text-3xl font-bold text-gray-700 dark:text-gray-300">
                                <span id="totalGeneration">0</span>
                                <span class="text-base">kWh</span>
                            </p>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">CO2削減量</h3>
                            <p class="text-3xl font-bold text-gray-700 dark:text-gray-300">
                                <span id="co2Reduction">0</span>
                                <span class="text-base">kg</span>
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const CHART_API_URL = "{{ route('public.generation.data') }}";
        let chart = null;

        // グラフの初期化
        function initChart(data) {
            const ctx = document.getElementById('powerChart').getContext('2d');
            
            if (chart) {
                chart.destroy();
            }

            chart = new Chart(ctx, {
                type: 'line',
                data: data.chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // 集計値の更新
            document.getElementById('totalGeneration').textContent = data.stats.total.toFixed(2);
            document.getElementById('co2Reduction').textContent = data.co2Reduction.toFixed(2);
            document.getElementById('electricityCost').textContent = Math.round(data.electricityCost).toLocaleString();
        }

        // データの更新
        async function updateChart() {
            try {
                const params = {
                    periodType: document.getElementById('periodType').value,
                    year: document.getElementById('year').value,
                    month: document.getElementById('month').value,
                    day: document.getElementById('day').value,
                    regionType: document.getElementById('regionType').value,
                    prefecture: document.getElementById('prefecture').value,
                    city: document.getElementById('city').value,
                    deviceType: document.getElementById('deviceType').value,
                    manufacturer: document.getElementById('manufacturer').value,
                    modelNumber: document.getElementById('modelNumber').value
                };

                const response = await fetch(`${CHART_API_URL}?${new URLSearchParams(params)}`);
                const data = await response.json();

                if (data.error) {
                    console.error(data.error);
                    return;
                }

                initChart(data);
            } catch (error) {
                console.error('データの取得に失敗しました:', error);
            }
        }

        // 期間選択の表示制御
        function updatePeriodSelectors() {
            const type = document.getElementById('periodType').value;
            document.getElementById('year').classList.toggle('hidden', !['yearly', 'monthly', 'daily'].includes(type));
            document.getElementById('month').classList.toggle('hidden', !['monthly', 'daily'].includes(type));
            document.getElementById('day').classList.toggle('hidden', type !== 'daily');
        }

        // 地域選択の表示制御
        function updateRegionSelectors() {
            const type = document.getElementById('regionType').value;
            document.getElementById('prefecture').classList.toggle('hidden', !['prefecture', 'city'].includes(type));
            document.getElementById('city').classList.toggle('hidden', type !== 'city');
        }

        // 機器選択の表示制御
        function updateDeviceSelectors() {
            const type = document.getElementById('deviceType').value;
            document.getElementById('manufacturer').classList.toggle('hidden', type !== 'manufacturer');
            document.getElementById('modelNumber').classList.toggle('hidden', type !== 'model');
        }

        // 市町村データの取得
        async function updateCityOptions() {
            const prefectureId = document.getElementById('prefecture').value;
            if (!prefectureId) return;

            try {
                const response = await fetch(`{{ route('regions.towns') }}?prefecture_id=${prefectureId}`);
                const cities = await response.json();

                const citySelect = document.getElementById('city');
                citySelect.innerHTML = '<option value="">市町村を選択</option>';
                
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
            } catch (error) {
                console.error('市町村データの取得に失敗しました:', error);
            }
        }

        // イベントリスナーの設定
        document.addEventListener('DOMContentLoaded', function() {
            // 初期データでグラフを描画
            updateChart();

            // 各セレクターの変更イベントを監視
            document.getElementById('periodType').addEventListener('change', () => {
                updatePeriodSelectors();
                updateChart();
            });

            document.getElementById('regionType').addEventListener('change', () => {
                updateRegionSelectors();
                updateChart();
            });

            document.getElementById('deviceType').addEventListener('change', () => {
                updateDeviceSelectors();
                updateChart();
            });

            ['year', 'month', 'day', 'manufacturer', 'modelNumber'].forEach(id => {
                document.getElementById(id)?.addEventListener('change', updateChart);
            });

            // 都道府県選択時の市町村更新
            document.getElementById('prefecture').addEventListener('change', () => {
                updateCityOptions();
                updateChart();
            });

            document.getElementById('city').addEventListener('change', updateChart);

            // 初期表示の設定
            updatePeriodSelectors();
            updateRegionSelectors();
            updateDeviceSelectors();
        });
    </script>
    @endpush
</x-guest-layout> 