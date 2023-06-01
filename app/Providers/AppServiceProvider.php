<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ActionRunner\ActionRunner;
use App\Services\Presenter\Presenter;
use App\Services\Presenter\PresenterContract;
use App\Services\TransactionProcessor\TransactionProcessor;
use App\Services\TransactionProcessor\TransactionProcessorContract;
use App\Services\TransactionReader\Adapters\PhpSpreadsheetAdapter;
use App\Services\TransactionReader\TransactionReader;
use Domain\Services\ActionRunner\ActionRunner as ActionRunnerInterface;
use Domain\Services\TransactionDispatcher\TransactionDispatcher;
use Domain\Services\TransactionDispatcher\TransactionDispatcherContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Intonate\TinkerZero\TinkerZeroServiceProvider;
use LaravelZero\Framework\Application;

final class AppServiceProvider extends ServiceProvider
{
    /** Register any application services. */
    public function register(): void
    {
        $this->app->singleton(ActionRunnerInterface::class, fn (Application $app) => new ActionRunner());
        $this->app->singleton(PresenterContract::class, fn (Application $app, array $params) => new Presenter($params[0], $params[1]));
        $this->app->singleton(TransactionDispatcherContract::class, fn (Application $app) => resolve(TransactionDispatcher::class));
        $this->app->singleton(TransactionProcessorContract::class, fn (Application $app) => resolve(TransactionProcessor::class));
        $this->app->singleton(TransactionReader::class, fn (Application $app) => new PhpSpreadsheetAdapter());

        if (! $this->isProduction()) {
            $this->app->register(TinkerZeroServiceProvider::class);
        }
    }

    /** Bootstrap any application services. */
    public function boot(): void
    {
        Model::preventAccessingMissingAttributes();
        Model::preventLazyLoading(! $this->isProduction());
        Model::preventSilentlyDiscardingAttributes();
    }

    private function isProduction(): bool
    {
        return $this->app->environment() === 'production';
    }
}
