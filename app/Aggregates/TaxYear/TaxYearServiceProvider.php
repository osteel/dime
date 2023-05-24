<?php

declare(strict_types=1);

namespace App\Aggregates\TaxYear;

use App\Aggregates\TaxYear\Repositories\TaxYearMessageRepository;
use App\Aggregates\TaxYear\Repositories\TaxYearRepository;
use App\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository;
use App\Services\ObjectHydration\PayloadSerializerFactory;
use App\Services\UuidEncoder\UuidEncoder;
use Domain\Aggregates\TaxYear\Projectors\TaxYearSummaryProjector;
use Domain\Aggregates\TaxYear\Repositories\TaxYearMessageRepository as TaxYearMessageRepositoryInterface;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository as TaxYearRepositoryInterface;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository as TaxYearSummaryRepositoryInterface;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\ExplicitlyMappedClassNameInflector;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcherChain;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use LaravelZero\Framework\Application;

class TaxYearServiceProvider extends ServiceProvider
{
    /** Register any application services. */
    public function register(): void
    {
        $this->app->singleton(
            TaxYearSummaryRepositoryInterface::class,
            fn (Application $app) => new TaxYearSummaryRepository(),
        );

        // @phpstan-ignore-next-line
        $classNameInflector = new ExplicitlyMappedClassNameInflector(config('eventsourcing.class_map'));

        // @phpstan-ignore-next-line
        $payloadSerializer = PayloadSerializerFactory::make(config('eventsourcing.hydrator_class_map'));

        $this->app->bind(TaxYearMessageRepositoryInterface::class, fn (Application $app) => new TaxYearMessageRepository(
            // @phpstan-ignore-next-line
            connection: $app->make(DatabaseManager::class)->connection(),
            tableName: 'tax_year_events',
            serializer: new ConstructingMessageSerializer($classNameInflector, $payloadSerializer),
            uuidEncoder: new UuidEncoder(),
        ));

        $this->app->bind(TaxYearRepositoryInterface::class, fn () => new TaxYearRepository(
            // @phpstan-ignore-next-line
            $this->app->make(TaxYearMessageRepositoryInterface::class),
            // @phpstan-ignore-next-line
            new MessageDispatcherChain(new SynchronousMessageDispatcher($this->app->make(TaxYearSummaryProjector::class))),
            new MessageDecoratorChain(new DefaultHeadersDecorator($classNameInflector)),
            $classNameInflector,
        ));
    }
}
