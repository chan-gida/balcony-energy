<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Generation;
use App\Models\Device;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PowerChart extends Component
{
    public $displayType = 'daily';
    public $selectedYear;
    public $selectedMonth;
    public $selectedDay;
    public $selectedDevice = 'all';
    public $devices;
    public $years;
    public $daysInMonth;

    public $totalPower = 0;
    public $averagePower = 0;
    public $maxPower = 0;

    public function mount()
    {
        $this->devices = Auth::user()->devices;
        $this->years = range(2020, now()->year);
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;
        $this->selectedDay = now()->day;
        $this->daysInMonth = CarbonImmutable::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
        $this->updateChartData();
    }

    public function updatedDisplayType()
    {
        Log::info('DisplayType updated:', ['displayType' => $this->displayType]);
        $this->updateChartData();
    }

    public function updatedSelectedYear()
    {
        Log::info('SelectedYear updated:', ['selectedYear' => $this->selectedYear]);
        $this->updateChartData();
    }

    public function updatedSelectedMonth()
    {
        Log::info('SelectedMonth updated:', ['selectedMonth' => $this->selectedMonth]);
        $this->daysInMonth = CarbonImmutable::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
        $this->updateChartData();
    }

    public function updatedSelectedDay()
    {
        Log::info('SelectedDay updated:', ['selectedDay' => $this->selectedDay]);
        $this->updateChartData();
    }

    public function updatedSelectedDevice()
    {
        Log::info('SelectedDevice updated:', ['selectedDevice' => $this->selectedDevice]);
        $this->updateChartData();
    }

    public function hydrate()
    {
        Log::info('Component was hydrated');
    }

    public function dehydrate()
    {
        Log::info('Component was dehydrated');
    }

    private function updateChartData()
    {
        $query = Generation::query()
            ->when($this->selectedDevice !== 'all', function ($query) {
                $query->where('device_id', $this->selectedDevice);
            })
            ->when($this->selectedDevice === 'all', function ($query) {
                $query->whereIn('device_id', Auth::user()->devices->pluck('id'));
            });

        // デバッグ出力を追加
        Log::info('Device IDs:', ['ids' => Auth::user()->devices->pluck('id')]);
        Log::info('Query SQL:', ['sql' => $query->toSql()]);
        Log::info('Query Bindings:', ['bindings' => $query->getBindings()]);

        // 期間に応じたデータの取得
        switch ($this->displayType) {
            case 'daily':
                $data = $this->getDailyData($query);
                break;
            case 'monthly':
                $data = $this->getMonthlyData($query);
                break;
            case 'yearly':
                $data = $this->getYearlyData($query);
                break;
            case 'all':
                $data = $this->getAllData($query);
                break;
        }

        // データの内容を確認
        Log::info('Chart Data:', [
            'labels' => $data['labels'],
            'power' => $data['power']
        ]);

        // 統計データの計算
        $this->totalPower = $data['power']->sum();
        $this->averagePower = $data['power']->average() ?? 0;
        $this->maxPower = $data['power']->max() ?? 0;

        // グラフデータの更新
        $chartData = [
            'labels' => $data['labels']->toArray(),
            'datasets' => [[
                'label' => '発電量',
                'data' => $data['power']->toArray(),
                'borderColor' => '#4B5563',
                'backgroundColor' => 'rgba(75, 85, 99, 0.1)',
                'tension' => 0.1
            ]]
        ];

        // デバッグ用にログ出力
        Log::info('Dispatching chart data:', [
            'labels_count' => count($chartData['labels']),
            'data_count' => count($chartData['datasets'][0]['data']),
            'labels' => $chartData['labels'],
            'data' => $chartData['datasets'][0]['data']
        ]);

        // データが空でないか確認
        if (empty($chartData['labels']) || empty($chartData['datasets'][0]['data'])) {
            Log::warning('Empty chart data detected!');
        }

        // イベントを送信
        $this->dispatch('updateChart', $chartData);
    }

    // 以下、各期間のデータ取得メソッド
    private function getDailyData($query)
    {
        $date = CarbonImmutable::create($this->selectedYear, $this->selectedMonth, $this->selectedDay);
        $data = $query->whereDate('generation_time', $date)
            ->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('H:i');
            });

        return [
            'labels' => $data->keys(),
            'power' => $data->map(function ($items) {
                return $items->sum('power');
            })
        ];
    }

    private function getMonthlyData($query)
    {
        $startDate = CarbonImmutable::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $data = $query->whereBetween('generation_time', [$startDate, $endDate])
            ->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('d');
            });

        return [
            'labels' => $data->keys()->map(function ($day) {
                return $day . '日';
            }),
            'power' => $data->map(function ($items) {
                return $items->sum('power');
            })
        ];
    }

    private function getYearlyData($query)
    {
        $startDate = CarbonImmutable::create($this->selectedYear, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();

        $data = $query->whereBetween('generation_time', [$startDate, $endDate])
            ->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('m');
            });

        return [
            'labels' => $data->keys()->map(function ($month) {
                return $month . '月';
            }),
            'power' => $data->map(function ($items) {
                return $items->sum('power');
            })
        ];
    }

    private function getAllData($query)
    {
        $data = $query->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('Y-m');
            });

        return [
            'labels' => $data->keys()->map(function ($yearMonth) {
                $date = CarbonImmutable::createFromFormat('Y-m', $yearMonth);
                return $date->format('Y年m月');
            }),
            'power' => $data->map(function ($items) {
                return $items->sum('power');
            })
        ];
    }

    public function render()
    {
        return view('livewire.power-chart');
    }
}
