<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Device; //追加

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 🔽 センサーデータを作成する
        Device::create([
            'user_id' => '1',
            'device_name' => 'tanaka_rp',
            'sensor_id' => 'INA260_a1',
            'api_token' => 'AAAAAAAA'
        ]);
        Device::create([
            'user_id' => '2',
            'device_name' => 'yamamoto_rp',
            'sensor_id' => 'INA260_a2',
            'api_token' => 'BBBBBBBB'
        ]);
    }
}
