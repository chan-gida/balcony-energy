<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use App\Models\Device; //追加：Device
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // リクエストヘッダーからAPIトークンを取得
        $token = $request->bearerToken() ?? $request->header('X-Device-API-Token');

        // トークンが指定されていない場合は認証エラー
        if (!$token) {
            return response()->json(['message' => 'API token is required'], 401);
        }

        // トークンに一致するデバイスを探す
        $device = Device::where('api_token', $token)->first();

        // デバイスが見つからない場合は認証エラー
        if (!$device) {
            return response()->json(['message' => 'Invalid API token'], 401);
        }

        // リクエストにユーザーIDを直接設定
        // $request->merge(['user_id' => $device->user_id]);
        // リクエストにdevice_idを直接設定
        $request->merge(['device_id' => $device->id]);

        return $next($request);
    }
}
