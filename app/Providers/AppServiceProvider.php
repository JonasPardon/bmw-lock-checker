<?php

namespace App\Providers;

use App\Services\BmwCarData\TokenStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TokenStore::class, fn () => new TokenStore(config('bmw.token_file')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
