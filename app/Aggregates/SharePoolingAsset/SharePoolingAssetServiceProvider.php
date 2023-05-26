<?php

declare(strict_types=1);

namespace App\Aggregates\SharePoolingAsset;

use App\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetMessageRepository;
use App\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetRepository;
use App\Services\ObjectHydration\PayloadSerializerFactory;
use App\Services\UuidEncoder\UuidEncoder;
use Domain\Aggregates\SharePoolingAsset\Reactors\SharePoolingAssetReactor;
use Domain\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetMessageRepository as SharePoolingAssetMessageRepositoryInterface;
use Domain\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetRepository as SharePoolingAssetRepositoryInterface;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\ExplicitlyMappedClassNameInflector;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcherChain;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use LaravelZero\Framework\Application;

final class SharePoolingAssetServiceProvider extends ServiceProvider
{
    /** Register any application services. */
    public function register(): void
    {
        // @phpstan-ignore-next-line
        $classNameInflector = new ExplicitlyMappedClassNameInflector(config('eventsourcing.class_map'));

        // @phpstan-ignore-next-line
        $payloadSerializer = PayloadSerializerFactory::make(config('eventsourcing.hydrator_class_map'));

        $this->app->bind(SharePoolingAssetMessageRepositoryInterface::class, fn (Application $app) => new SharePoolingAssetMessageRepository(
            // @phpstan-ignore-next-line
            connection: $app->make(DatabaseManager::class)->connection(),
            tableName: 'share_pooling_asset_events',
            serializer: new ConstructingMessageSerializer($classNameInflector, $payloadSerializer),
            uuidEncoder: new UuidEncoder(),
        ));

        $this->app->bind(SharePoolingAssetRepositoryInterface::class, fn () => new SharePoolingAssetRepository(
            // @phpstan-ignore-next-line
            $this->app->make(SharePoolingAssetMessageRepositoryInterface::class),
            // @phpstan-ignore-next-line
            new MessageDispatcherChain(new SynchronousMessageDispatcher($this->app->make(SharePoolingAssetReactor::class))),
            new MessageDecoratorChain(new DefaultHeadersDecorator($classNameInflector)),
            $classNameInflector,
        ));
    }
}
