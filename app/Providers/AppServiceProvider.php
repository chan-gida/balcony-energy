<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Livewireの設定を行う場合はここに記述
        // 例: Livewireのスクリプトルートを設定する場合
        // Livewire::setScriptRoute(function ($handle) {
        //     return Route::get('/livewire/livewire.js', $handle)->name('livewire.js');
        // });

        // Alpine.jsの自動読み込みを無効化する方法はLivewireのバージョンによって異なります
        // Livewire v3では以下のようにします
        // Livewire::setAlpineImports([]);
        
        // 代わりに、app.jsでAlpineの初期化を制御することをお勧めします

        // サブディレクトリのパスを設定
        if ($this->app->environment('production')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }
    }
}
