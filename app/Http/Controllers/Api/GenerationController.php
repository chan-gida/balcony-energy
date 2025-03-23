<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Generation; // 追加：Generation

class GenerationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //バリデーションで受け取ったJSONデータの必要な項目をチェック
        $validatedData = $request->validate([
            // 'user_id'      => 'required|numeric',
            'generation_time' => 'required|date',
            'current'   => 'required|numeric',
            'voltage'   => 'required|numeric',
            'power'     => 'required|numeric',
            // 'device_id' => 'required|numeric', // device_idをバリデーションに追加
        ]);

        // リクエストからdevice_idを追加
        // この値はEnsureDeviceTokenミドルウェアで追加される
        $validatedData['device_id'] = $request->device_id;

        // 直接Generationモデルを使用して作成
        $generation = Generation::create($validatedData);

        return response()->json($generation, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
