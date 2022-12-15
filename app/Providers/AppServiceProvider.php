<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\TransactionReader\Adapters\PhpSpreadsheetAdapter;
use App\Services\TransactionReader\TransactionReader;
use Illuminate\Support\ServiceProvider;
use Intonate\TinkerZero\TinkerZeroServiceProvider;
use LaravelZero\Framework\Application;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TransactionReader::class, fn (Application $app) => new PhpSpreadsheetAdapter());

        if ($this->app->environment() !== 'production') {
            $this->app->register(TinkerZeroServiceProvider::class);
        }
    }
}
