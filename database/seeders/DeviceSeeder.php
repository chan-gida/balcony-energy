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
            'device_name' => 'tanaka_PV1',
            'facility_maker' => 'AFERIY',
            'facility_name' => 'ETFE 200W',
            'api_token' => 'AAAAAAAA'
        ]);
    }
}
