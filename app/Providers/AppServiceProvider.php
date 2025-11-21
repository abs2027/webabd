<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // ▼▼▼ TAMBAHKAN BARIS INI ▼▼▼
        ini_set('memory_limit', '512M'); 
        // ▲▲▲ SELESAI ▲▲▲
        
        // ... kode lain jika ada (misal Paginator, dll)
    }
}
