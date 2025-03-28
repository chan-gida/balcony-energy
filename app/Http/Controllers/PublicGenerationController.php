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

            // 都道府県一覧を取得（region_numの1万桁以上の数値でグループ化）
            $prefectures = Region::select('prefecture_name as name', 'id')
                ->selectRaw('FLOOR(region_num/10000) as prefecture_code')
                ->whereNotNull('prefecture_name')
                ->whereRaw('region_num >= 10000')  // 1万以上の数値のみを対象
                ->groupBy('prefecture_code')  // 都道府県コードでグループ化
                ->orderBy('prefecture_code')  // 都道府県コード順（01-47）で並び替え
                ->get();

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
            Log::error('Error in PublicGenerationController@index: ' . $e->getMessage());
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

            // 都道府県コードを取得
            $prefectureCode = Region::where('id', $prefectureId)
                ->selectRaw('FLOOR(region_num/10000) as prefecture_code')
                ->value('prefecture_code');

            if (!$prefectureCode) {
                return response()->json([]);
            }

            // 指定された都道府県コードに属する市町村を取得
            $towns = Region::select('id', 'town_name as name')
                ->whereRaw('FLOOR(region_num/10000) = ?', [$prefectureCode])
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
                ->leftJoin('devices', 'generations.device_id', '=', 'devices.id')
                ->leftJoin('users', 'devices.user_id', '=', 'users.id')
                ->leftJoin('region_user', 'users.id', '=', 'region_user.user_id')
                ->leftJoin('regions', 'region_user.region_id', '=', 'regions.id');

            // データ件数のデバッグ
            $generationCount = Generation::count();
            $deviceCount = Device::count();
            $regionCount = Region::count();
            $regionUserCount = DB::table('region_user')->count();

            Log::debug('Table counts:', [
                'generations' => $generationCount,
                'devices' => $deviceCount,
                'regions' => $regionCount,
                'region_user' => $regionUserCount
            ]);

            // 地域フィルター
            if ($regionType === 'prefecture' && $prefecture) {
                $query->whereExists(function ($subquery) use ($prefecture) {
                    $subquery->select(DB::raw(1))
                        ->from('regions')
                        ->whereColumn('regions.id', 'region_user.region_id')
                        ->where('regions.id', $prefecture);
                });
            } elseif ($regionType === 'city' && $city) {
                $query->where('regions.id', $city);
            }

            // デバイスフィルター
            if ($deviceType === 'manufacturer' && $manufacturer) {
                $query->where('devices.facility_maker', $manufacturer);
            } elseif ($deviceType === 'model' && $modelNumber) {
                $query->where('devices.facility_name', $modelNumber);
            }

            // クエリのデバッグ
            $debugQuery = $query->toSql();
            $debugBindings = $query->getBindings();
            Log::debug('Generated SQL:', [
                'query' => $debugQuery,
                'bindings' => $debugBindings
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

            // 結果のデバッグ
            Log::debug('Query result:', [
                'displayType' => $displayType,
                'total' => $data['total'],
                'labels' => $data['labels'],
                'data' => $data['data']
            ]);

            // CO2削減量と電気代削減量の計算
            $co2Reduction = $data['total'] * 0.472;
            $electricityCost = $data['total'] * 27;

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
                'co2Reduction' => $co2Reduction,
                'electricityCost' => $electricityCost
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PublicGenerationController@getData: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
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
