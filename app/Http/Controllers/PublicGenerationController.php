<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Models\Device;
use App\Models\Region;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicGenerationController extends Controller
{
    public function index()
    {
        try {
            Log::debug('PublicGenerationController@index: Starting method execution', [
                'request' => request()->all(),
                'session' => session()->all(),
                'user' => auth()->user() ? auth()->user()->toArray() : null
            ]);

            // 都道府県一覧を取得（prefecture_numでグループ化）
            $prefectures = Region::select('prefecture_name as name')
                ->selectRaw('MIN(id) as id')
                ->selectRaw('MIN(prefecture_num) as prefecture_num')
                ->whereNotNull('prefecture_name')
                ->groupBy('prefecture_name')
                ->orderBy('prefecture_num')
                ->get();

            // 都道府県データのデバッグ
            Log::debug('Prefecture data:', [
                'count' => $prefectures->count(),
                'data' => $prefectures->toArray()
            ]);

            // デバイスのメーカー一覧を取得
            $manufacturers = Device::select('facility_maker as manufacturer')
                ->whereNotNull('facility_maker')
                ->distinct()
                ->orderBy('facility_maker')
                ->get();

            // デバイスの型番一覧を取得
            $models = Device::select('facility_name as model_number')
                ->whereNotNull('facility_name')
                ->distinct()
                ->orderBy('facility_name')
                ->get();

            Log::debug('View data:', [
                'prefectures_count' => $prefectures->count(),
                'manufacturers_count' => $manufacturers->count(),
                'models_count' => $models->count()
            ]);

            return view('public-generation', [
                'prefectures' => $prefectures,
                'manufacturers' => $manufacturers,
                'models' => $models,
                'years' => range(2020, now()->year),
                'currentYear' => now()->year,
                'currentMonth' => now()->month,
                'currentDay' => now()->day,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PublicGenerationController@index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'データの取得に失敗しました');
        }
    }

    /**
     * 指定された都道府県コードに属する市町村一覧を取得
     */
    public function getTowns(Request $request)
    {
        try {
            $prefectureId = $request->input('prefecture_id');

            // 選択された都道府県のprefecture_numを取得
            $prefectureNum = Region::where('id', $prefectureId)
                ->value('prefecture_num');

            if (!$prefectureNum) {
                return response()->json([]);
            }

            // 指定されたprefecture_numに属する市町村を取得
            $towns = Region::select('id', 'town_name as name')
                ->where('prefecture_num', $prefectureNum)
                ->whereNotNull('town_name')
                ->orderBy('region_num')
                ->get();

            return response()->json($towns);
        } catch (\Exception $e) {
            Log::error('Error in PublicGenerationController@getTowns: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch towns'], 500);
        }
    }

    public function getData(Request $request)
    {
        try {
            // displayTypeをperiodTypeから設定
            $periodType = $request->input('periodType', 'all');
            $displayType = $periodType; // periodTypeをdisplayTypeとして使用

            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);
            $day = $request->input('day', now()->day);

            $regionType = $request->input('regionType', 'all');
            $prefecture = $request->input('prefecture');
            $city = $request->input('city');

            $deviceType = $request->input('deviceType', 'all');
            $manufacturer = $request->input('manufacturer');
            $modelNumber = $request->input('modelNumber');

            // リクエストパラメータのデバッグ
            Log::debug('Request parameters:', [
                'periodType' => $periodType,
                'displayType' => $displayType,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'regionType' => $regionType,
                'prefecture' => $prefecture,
                'city' => $city,
                'deviceType' => $deviceType,
                'manufacturer' => $manufacturer,
                'modelNumber' => $modelNumber
            ]);

            // 基本クエリの構築
            $query = Generation::query()
                ->select('generations.*')  // generationsテーブルの全カラムを明示的に選択
                ->distinct();  // 重複を排除

            // 期間フィルターを先に適用
            $displayType = $request->input('periodType', 'all');
            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);
            $day = $request->input('day', now()->day);

            switch ($displayType) {
                case 'daily':
                    $query->whereDate('generation_time', CarbonImmutable::create($year, $month, $day));
                    break;
                case 'monthly':
                    $query->whereYear('generation_time', $year)
                        ->whereMonth('generation_time', $month);
                    break;
                case 'yearly':
                    $query->whereYear('generation_time', $year);
                    break;
            }

            // 地域フィルター
            $regionType = $request->input('regionType', 'all');
            $prefecture = $request->input('prefecture');
            $city = $request->input('city');
            
            if ($regionType === 'prefecture' && $prefecture) {
                // 選択された都道府県のprefecture_numを取得
                $prefectureNum = Region::where('id', $prefecture)
                    ->value('prefecture_num');

                if ($prefectureNum) {
                    // 同じprefecture_numを持つ全てのregion.idを取得
                    $regionIds = Region::where('prefecture_num', $prefectureNum)
                        ->pluck('id');

                    // 取得したregion.idと紐づいたgenerationデータを取得
                    $query->join('devices', 'generations.device_id', '=', 'devices.id')
                        ->join('users', 'devices.user_id', '=', 'users.id')
                        ->join('region_user', 'users.id', '=', 'region_user.user_id')
                        ->whereIn('region_user.region_id', $regionIds);
                }
            } elseif ($regionType === 'city' && $city) {
                // 選択された市町村のregion_numを取得
                $regionNum = Region::where('id', $city)
                    ->value('region_num');

                if ($regionNum) {
                    // 同じregion_numを持つregion.idを取得
                    $regionId = Region::where('region_num', $regionNum)
                        ->value('id');

                    // 取得したregion.idと紐づいたgenerationデータを取得
                    $query->join('devices', 'generations.device_id', '=', 'devices.id')
                        ->join('users', 'devices.user_id', '=', 'users.id')
                        ->join('region_user', 'users.id', '=', 'region_user.user_id')
                        ->where('region_user.region_id', $regionId);
                }
            }

            // デバイスフィルター
            $deviceType = $request->input('deviceType', 'all');
            $manufacturer = $request->input('manufacturer');
            $modelNumber = $request->input('modelNumber');

            if ($deviceType === 'manufacturer' && $manufacturer) {
                $query->where('devices.facility_maker', $manufacturer);
            } elseif ($deviceType === 'model' && $modelNumber) {
                $query->where('devices.facility_name', $modelNumber);
            }

            // デバッグ用のクエリログ
            Log::debug('Final SQL Query:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // クエリ実行前の件数確認
            $countBeforeFilter = $query->count();
            Log::debug('Records count before date filter:', ['count' => $countBeforeFilter]);

            // 期間に応じたデータの取得
            $data = match ($displayType) {
                'daily' => $this->getDailyData($query, $year, $month, $day),
                'monthly' => $this->getMonthlyData($query, $year, $month),
                'yearly' => $this->getYearlyData($query, $year),
                'all' => $this->getAllData($query),
                default => $this->getAllData($query),  // デフォルトを全期間に変更
            };

            return response()->json([
                'chartData' => [
                    'labels' => $data['labels'],
                    'datasets' => [[
                        'label' => '発電量',
                        'data' => $data['data'],
                        'borderColor' => '#4B5563',
                        'backgroundColor' => '#4B556333',
                        'tension' => 0.1
                    ]]
                ],
                'stats' => [
                    'total' => $data['total'],
                    'average' => $data['average'],
                    'max' => $data['max']
                ],
                'unit' => $data['unit'],
                'co2Reduction' => $data['total'] * 0.472
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PublicGenerationController@getData: ' . $e->getMessage());
            return response()->json(['error' => 'データの取得に失敗しました: ' . $e->getMessage()], 500);
        }
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
            ->select(DB::raw('DATE_FORMAT(generation_time, "%H:00") as hour'), DB::raw('SUM(power) * 0.0000005 as total_power'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total_power', 'hour');

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
            ->select(DB::raw('DAY(generation_time) as day'), DB::raw('SUM(power) * 0.0000005 as total_power'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_power', 'day');

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
            ->select(DB::raw('MONTH(generation_time) as month'), DB::raw('SUM(power) * 0.0000005 as total_power'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_power', 'month');

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
        $data = $query->select(DB::raw('YEAR(generation_time) as year'), DB::raw('SUM(power) * 0.0000005 as total_power'))
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('total_power', 'year');

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
