<?php

declare(strict_types=1);

namespace App\Nft;

use Domain\Nft\Repositories\NftMessageRepository as NftMessageRepositoryInterface;
use Domain\Nft\Repositories\NftRepository as NftRepositoryInterface;
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

class NftServiceProvider extends ServiceProvider
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

        $this->app->bind(NftMessageRepositoryInterface::class, fn (Application $app) => new NftMessageRepository(
            // @phpstan-ignore-next-line
            connection: $app->make(DatabaseManager::class)->connection(),
            tableName: 'nft_events',
            serializer: new ConstructingMessageSerializer(
                $classNameInflector,
                new PayloadSerializerSupportingObjectMapperAndSerializablePayload(),
            ),
            uuidEncoder: new StringUuidEncoder(),
        ));

        $this->app->bind(NftRepositoryInterface::class, fn () => new NftRepository(
            // @phpstan-ignore-next-line
            $this->app->make(NftMessageRepositoryInterface::class),
            new MessageDispatcherChain(new SynchronousMessageDispatcher()),
            new MessageDecoratorChain(new DefaultHeadersDecorator($classNameInflector)),
            $classNameInflector,
        ));
    }
}
