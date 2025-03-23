<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
// 🔽 2行追加
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 🔽 ユーザ作成する
        User::create([
            'name' => '田中太郎',
            'email' => 'tanaka@email.com',
            'password' => Hash::make('password'),
        ]);
        User::create([
            'name' => '山本花子',
            'email' => 'yamamoto@email.com',
            'password' => Hash::make('password'),
        ]);
    }
}
