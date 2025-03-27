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
        try {
            $displayType = $request->input('displayType', 'daily');
            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);
            $day = $request->input('day', now()->day);
            $deviceId = $request->input('deviceId', 'all');

            // ユーザーのデバイスを取得
            $devices = Auth::user()->devices;

            // デバイスの色を動的に生成
            $deviceColors = ['all' => '#4B5563']; // デフォルトの色（全デバイス）
            
            // HSL色空間を使用して、均等に分布した色を生成
            $devices->each(function ($device, $index) use (&$deviceColors, $devices) {
                // 色相を均等に分布させる（0-360度）
                $hue = ($index / $devices->count()) * 360;
                // 彩度と明度は固定値を使用して見やすい色を生成
                $saturation = 70; // 70%
                $lightness = 50;  // 50%
                
                // HSLからHEXに変換
                $deviceColors[$device->id] = $this->hslToHex($hue, $saturation, $lightness);
            });

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

            // デバイスの色を設定
            $color = $deviceColors[$deviceId] ?? $deviceColors['all'];
            $data['color'] = $color;

            // CO2削減量と電気代削減量の計算
            $co2Reduction = $data['total'] * 0.472; // 1kWhあたり0.472kg-CO2として計算
            $electricityCost = $data['total'] * 27; // 1kWhあたり27円として計算

            if ($request->ajax()) {
                $data['co2Reduction'] = $co2Reduction;
                $data['electricityCost'] = $electricityCost;
                return response()->json($data);
            }

            // 初期表示用のデータを設定
            return view('dashboard', [
                'chartData' => $data,
                'years' => range(2020, now()->year),
                'currentYear' => $year,
                'currentMonth' => $month,
                'currentDay' => $day,
                'devices' => $devices,
                'selectedDevice' => $deviceId,
                'displayType' => $displayType,
                'deviceColors' => $deviceColors,
                'co2Reduction' => $co2Reduction,
                'electricityCost' => $electricityCost,
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json(['error' => 'データの取得に失敗しました'], 500);
            }
            
            return back()->with('error', 'データの取得に失敗しました');
        }
    }

    public function getChartData(Request $request)
    {
        $displayType = $request->input('displayType', 'daily');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $day = $request->input('day', now()->day);
        $deviceId = $request->input('device', 'all');

        $query = Generation::query();
        
        // デバイスフィルタリング
        if ($deviceId !== 'all') {
            $query->where('device_id', $deviceId);
        } else {
            $query->whereIn('device_id', Auth::user()->devices->pluck('id'));
        }

        // 期間に応じたデータの取得
        $data = match ($displayType) {
            'daily' => $this->getDailyData($query, $year, $month, $day),
            'monthly' => $this->getMonthlyData($query, $year, $month),
            'yearly' => $this->getYearlyData($query, $year),
            'all' => $this->getAllData($query),
            default => $this->getDailyData($query, $year, $month, $day),
        };

        // Chart.js形式のデータに変換
        $chartData = [
            'labels' => $data['labels'],
            'datasets' => [[
                'label' => '発電量',
                'data' => $data['data'],
                'borderColor' => '#4B5563',
                'backgroundColor' => 'rgba(75, 85, 99, 0.1)',
                'tension' => 0.1
            ]]
        ];

        // 統計データを追加
        $response = [
            'chartData' => $chartData,
            'stats' => [
                'total' => $data['total'],
                'average' => $data['average'],
                'max' => $data['max']
            ]
        ];

        return response()->json($response);
    }

    private function getDailyData($query, $year, $month, $day)
    {
        $date = CarbonImmutable::create($year, $month, $day);
        
        // 0時から23時までの全時間帯を作成
        $hours = collect(range(0, 23))->mapWithKeys(function ($hour) {
            $timeStr = sprintf('%02d:00', $hour);
            return [$timeStr => 0];
        });

        // データベースからの取得結果
        $dbData = $query->whereDate('generation_time', $date)
            ->orderBy('generation_time')
            ->get()
            ->groupBy(function ($item) {
                return CarbonImmutable::parse($item->generation_time)->format('H:00');
            })
            ->map(function ($items) {
                return $items->sum('power') * 0.0000005; // kWhに変換
            });

        // 全時間帯のデータを結合（欠損値は0として扱う）
        $data = $hours->merge($dbData)->sortKeys();

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
                return $items->sum('power') * 0.0000005;
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
                return $items->sum('power') * 0.0000005;
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
                return $items->sum('power') * 0.0000005;
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

    // HSLカラーをHEXカラーに変換するヘルパーメソッド
    private function hslToHex($hue, $saturation, $lightness)
    {
        $hue /= 360;
        $saturation /= 100;
        $lightness /= 100;

        if ($saturation == 0) {
            $red = $green = $blue = $lightness;
        } else {
            $q = $lightness < 0.5 
                ? $lightness * (1 + $saturation) 
                : $lightness + $saturation - $lightness * $saturation;
            $p = 2 * $lightness - $q;

            $red = $this->hueToRgb($p, $q, $hue + 1/3);
            $green = $this->hueToRgb($p, $q, $hue);
            $blue = $this->hueToRgb($p, $q, $hue - 1/3);
        }

        $red = round($red * 255);
        $green = round($green * 255);
        $blue = round($blue * 255);

        return sprintf('#%02x%02x%02x', $red, $green, $blue);
    }

    private function hueToRgb($p, $q, $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }
} 