<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ActionRunner\ActionRunner;
use App\Services\TransactionReader\Adapters\PhpSpreadsheetAdapter;
use App\Services\TransactionReader\TransactionReader;
use Domain\Services\ActionRunner\ActionRunner as ActionRunnerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Intonate\TinkerZero\TinkerZeroServiceProvider;
use LaravelZero\Framework\Application;

class AppServiceProvider extends ServiceProvider
{
    /** Register any application services. */
    public function register(): void
    {
        $this->app->singleton(ActionRunnerInterface::class, fn (Application $app) => new ActionRunner());
        $this->app->singleton(TransactionReader::class, fn (Application $app) => new PhpSpreadsheetAdapter());

        if (! $this->isProduction()) {
            $this->app->register(TinkerZeroServiceProvider::class);
        }
    }

    /** Bootstrap any application services. */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->isProduction());
    }

    private function isProduction(): bool
    {
        return $this->app->environment() === 'production';
    }
}
