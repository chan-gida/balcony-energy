<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;

    //アプリケーション側から規定できるカラムを指定
    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'sensor_id',
        'api_token',
    ];


    //Userモデルとのリレーション・１対多を規定
    public function devices()
    {
        return $this->belongsTo(Device::class);
    }
}
