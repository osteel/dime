<?php

declare(strict_types=1);

namespace App\Aggregates\NonFungibleAsset;

use App\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetMessageRepository;
use App\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use App\Services\ObjectHydration\PayloadSerializerFactory;
use App\Services\UuidEncoder\UuidEncoder;
use Domain\Aggregates\NonFungibleAsset\Reactors\NonFungibleAssetReactor;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetMessageRepository as NonFungibleAssetMessageRepositoryInterface;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository as NonFungibleAssetRepositoryInterface;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\ExplicitlyMappedClassNameInflector;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcherChain;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use LaravelZero\Framework\Application;

final class NonFungibleAssetServiceProvider extends ServiceProvider
{
    /** Register any application services. */
    public function register(): void
    {
        // @phpstan-ignore-next-line
        $classNameInflector = new ExplicitlyMappedClassNameInflector(config('eventsourcing.class_map'));

        // @phpstan-ignore-next-line
        $payloadSerializer = PayloadSerializerFactory::make(config('eventsourcing.hydrator_class_map'));

        $this->app->bind(NonFungibleAssetMessageRepositoryInterface::class, fn (Application $app) => new NonFungibleAssetMessageRepository(
            // @phpstan-ignore-next-line
            connection: $app->make(DatabaseManager::class)->connection(),
            tableName: 'non_fungible_asset_events',
            serializer: new ConstructingMessageSerializer($classNameInflector, $payloadSerializer),
            uuidEncoder: new UuidEncoder(),
        ));

        $this->app->bind(NonFungibleAssetRepositoryInterface::class, fn () => new NonFungibleAssetRepository(
            // @phpstan-ignore-next-line
            $this->app->make(NonFungibleAssetMessageRepositoryInterface::class),
            // @phpstan-ignore-next-line
            new MessageDispatcherChain(new SynchronousMessageDispatcher($this->app->make(NonFungibleAssetReactor::class))),
            new MessageDecoratorChain(new DefaultHeadersDecorator($classNameInflector)),
            $classNameInflector,
        ));
    }
}
