<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use Illuminate\Http\Request;

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
     * Show the form for creating a new resource.
     */
    public function create()
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
            'user_id'      => 'required|numeric',
            'generation_time' => 'required|date',
            'current'   => 'required|numeric',
            'voltage'   => 'required|numeric',
            'power'     => 'required|numeric',
        ]);
        //リクエストユーザーの発電量データを生成
        // user_idは自動的に取得
        $generation = $request->user()->generations()->create($validatedData);
        return response()->json($generation, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Generation $generation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Generation $generation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Generation $generation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Generation $generation)
    {
        //
    }
}
