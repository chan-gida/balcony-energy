<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); //追加：ユーザーID
            $table->string('device_name'); //追加：機器名
            $table->string('facility_maker'); //追加：機器メーカー
            $table->string('facility_name'); //追加：センサーID
            $table->string('api_token')->default(0); //追加：デバイス認識用トークン
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
