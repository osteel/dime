<?php

declare(strict_types=1);

namespace App\TaxYear;

use Domain\TaxYear\Repositories\TaxYearMessageRepository as TaxYearMessageRepositoryInterface;
use Domain\TaxYear\Repositories\TaxYearRepository as TaxYearRepositoryInterface;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\ExplicitlyMappedClassNameInflector;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcherChain;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\PayloadSerializerSupportingObjectMapperAndSerializablePayload;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use EventSauce\UuidEncoding\StringUuidEncoder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use LaravelZero\Framework\Application;

class TaxYearServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // @phpstan-ignore-next-line
        $classNameInflector = new ExplicitlyMappedClassNameInflector(config('eventsourcing.class_map'));

        $this->app->bind(TaxYearMessageRepositoryInterface::class, fn (Application $app) => new TaxYearMessageRepository(
            // @phpstan-ignore-next-line
            connection: $app->make(DatabaseManager::class)->connection(),
            tableName: 'tax_year_events',
            serializer: new ConstructingMessageSerializer(
                $classNameInflector,
                new PayloadSerializerSupportingObjectMapperAndSerializablePayload(),
            ),
            uuidEncoder: new StringUuidEncoder(),
        ));

        $this->app->bind(TaxYearRepositoryInterface::class, fn () => new TaxYearRepository(
            // @phpstan-ignore-next-line
            $this->app->make(TaxYearMessageRepositoryInterface::class),
            new MessageDispatcherChain(new SynchronousMessageDispatcher()),
            new MessageDecoratorChain(new DefaultHeadersDecorator($classNameInflector)),
            $classNameInflector,
        ));
    }
}
