<?php

namespace App\Providers;

use Domain\Repositories\NftRepository;
use Illuminate\Support\ServiceProvider;
use LaravelZero\Framework\Application;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //$this->app->singleton(NftRepository::class, fn (Application $app) => new NftRepository(...));
    }
}
