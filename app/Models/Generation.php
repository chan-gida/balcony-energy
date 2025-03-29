<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Generation extends Model
{
    /** @use HasFactory<\Database\Factories\GenerationFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'generation_time',
        'current',
        'voltage',
        'power'
    ];

    //Userモデルとのリレーション・多対１を規定
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
