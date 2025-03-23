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
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->constrained()->cascadeOnDelete(); //追加：ユーザーID
            // $table->foreignId('equipment_id')->constrained()->cascadeOnDelete(); //追加設備ID
            $table->foreignId('device_id')->constrained()->cascadeOnDelete(); //追加：センサーID
            // $table->integer('device_id'); //追加：センサーIDのテスト用
            $table->dateTime('generation_time'); //追加：発電日時
            $table->float('current')->default(0); //追加：電流値
            $table->float('voltage')->default(0); //追加：電圧値
            $table->float('power')->default(0); //追加：電力値           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
