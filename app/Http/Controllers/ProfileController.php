<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\Region;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        // 都道府県一覧を取得（prefecture_numでグループ化）
        $prefectures = Region::select('prefecture_name as name')
            ->selectRaw('MIN(id) as id')
            ->selectRaw('MIN(prefecture_num) as prefecture_num')
            ->whereNotNull('prefecture_name')
            ->groupBy('prefecture_name')
            ->orderBy('prefecture_num')
            ->get();

        // ユーザーの現在のリージョン情報を取得
        $userRegion = $request->user()->regions->first();
        $towns = [];
        
        if ($userRegion) {
            // ユーザーの都道府県に属する市区町村を取得
            $towns = Region::where('prefecture_num', $userRegion->prefecture_num)
                ->whereNotNull('town_name')
                ->orderBy('region_num')
                ->get();
        }

        return view('profile.edit', [
            'user' => $request->user(),
            'prefectures' => $prefectures,
            'towns' => $towns,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // リージョン情報の更新
        if ($request->has('region_id')) {
            // 既存のリージョン関連を取得
            $existingRegion = $user->regions()->first();

            if ($existingRegion) {
                // 既存のレコードを更新
                $user->regions()->updateExistingPivot(
                    $existingRegion->id,
                    [
                        'region_id' => $request->region_id,
                        'updated_at' => now()
                    ]
                );
            } else {
                // 新規レコードを作成
                $user->regions()->attach($request->region_id);
            }
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    // 市区町村取得用のAPIエンドポイント
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
            return response()->json(['error' => 'Failed to fetch towns'], 500);
        }
    }
}
