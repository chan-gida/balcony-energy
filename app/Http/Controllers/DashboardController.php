<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $displayType = $request->input('displayType', 'daily');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $day = $request->input('day', now()->day);
        $deviceId = $request->input('deviceId', 'all');

        // クエリの基本部分
        $query = Generation::query()
            ->when($deviceId !== 'all', function ($query) use ($deviceId) {
                $query->where('device_id', $deviceId);
            })
            ->when($deviceId === 'all', function ($query) {
                $query->whereIn('device_id', Auth::user()->devices->pluck('id'));
            });

        // 期間に応じたデータの取得
        $data = match ($displayType) {
            'daily' => $this->getDailyData($query, $year, $month, $day),
            'monthly' => $this->getMonthlyData($query, $year, $month),
            'yearly' => $this->getYearlyData($query, $year),
            'all' => $this->getAllData($query),
            default => $this->getDailyData($query, $year, $month, $day),
        };

        if ($request->ajax()) {
            return response()->json($data);
        }

        // 初期表示用のデータを設定
        return view('dashboard', [
            'chartData' => $data,
            'years' => range(2020, now()->year),
            'currentYear' => $year,
            'currentMonth' => $month,
            'currentDay' => $day,
            'devices' => Auth::user()->devices,
            'selectedDevice' => $deviceId,
            'displayType' => $displayType,
        ]);
    }

    private function getDailyData($query, $year, $month, $day)
    {
        $date = CarbonImmutable::create($year, $month, $day);
        $data = $query->whereDate('generation_time', $date)
            ->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('H:i');
            })
            ->map(function ($items) {
                return $items->sum('power') * 0.0005; // kWhに変換
            });

        return [
            'labels' => $data->keys()->toArray(),
            'data' => $data->values()->toArray(),
            'unit' => '時間',
            'total' => $data->sum(),
            'average' => $data->avg(),
            'max' => $data->max(),
        ];
    }

    private function getMonthlyData($query, $year, $month)
    {
        $startDate = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->endOfMonth();

        $data = $query->whereBetween('generation_time', [$startDate, $endDate])
            ->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('d');
            })
            ->map(function ($items) {
                return $items->sum('power') * 0.0005;
            });

        return [
            'labels' => $data->keys()->map(fn($day) => $day . '日')->toArray(),
            'data' => $data->values()->toArray(),
            'unit' => '日',
            'total' => $data->sum(),
            'average' => $data->avg(),
            'max' => $data->max(),
        ];
    }

    private function getYearlyData($query, $year)
    {
        $startDate = CarbonImmutable::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->endOfYear();

        $data = $query->whereBetween('generation_time', [$startDate, $endDate])
            ->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('m');
            })
            ->map(function ($items) {
                return $items->sum('power') * 0.0005;
            });

        return [
            'labels' => $data->keys()->map(fn($month) => $month . '月')->toArray(),
            'data' => $data->values()->toArray(),
            'unit' => '月',
            'total' => $data->sum(),
            'average' => $data->avg(),
            'max' => $data->max(),
        ];
    }

    private function getAllData($query)
    {
        $data = $query->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('Y');
            })
            ->map(function ($items) {
                return $items->sum('power') * 0.0005;
            });

        return [
            'labels' => $data->keys()->map(fn($year) => $year . '年')->toArray(),
            'data' => $data->values()->toArray(),
            'unit' => '年',
            'total' => $data->sum(),
            'average' => $data->avg(),
            'max' => $data->max(),
        ];
    }
} 