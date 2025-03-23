<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 現在ログインしている最新のセンサーから表示
        // $devices = Device::with('user')->latest()->get();
        $devices = auth()->user()->devices()->latest()->get();
        return view('devices.index', compact('devices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //追加
        return view('devices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // APIトークンを生成
        $apiToken = \Illuminate\Support\Str::random(32); // 32文字のランダム文字列を生成

        // リクエストデータを取得し、APIトークンを追加
        $deviceData = $request->only('device_name', 'sensor_id');
        $deviceData['api_token'] = $apiToken;

        // 新しいデバイスを作成
        $device = $request->user()->devices()->create($deviceData);

        // $request->user()->devices()->create($request->only('device_name', 'sensor_id', 'api_token'));
        // return response()->json(['message' => 'Sensor created successfully']); //テスト用
        return redirect()->route('devices.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Device $device)
    {
        // このデバイスが現在のユーザーのものかチェック
        if ($device->user_id !== auth()->id()) {
            abort(403, '他のユーザーのデバイスにはアクセスできません');
        }

        return view('devices.show', compact('device'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Device $device)
    {
        // このデバイスが現在のユーザーのものかチェック
        if ($device->user_id !== auth()->id()) {
            abort(403, '他のユーザーのデバイスは編集できません');
        }

        return view('devices.edit', compact('device'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Device $device)
    {
        // このデバイスが現在のユーザーのものかチェック
        if ($device->user_id !== auth()->id()) {
            abort(403, '他のユーザーのデバイスは編集できません');
        }
        $device->update($request->all());
        // return response()->json(['message' => 'Sensor updated successfully']); //テスト用
        return view('devices.show', compact('device'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Device $device)
    {
        // このデバイスが現在のユーザーのものかチェック
        if ($device->user_id !== auth()->id()) {
            abort(403, '他のユーザーのデバイスは編集できません');
        }
        $device->delete();
        // return response()->json(['message' => 'Sensor deleted successfully']); //テスト用
        return redirect()->route('devices.index');
    }
}
