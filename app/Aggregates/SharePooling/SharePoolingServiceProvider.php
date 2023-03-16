<?php

declare(strict_types=1);

namespace App\Aggregates\SharePooling;

use App\Aggregates\SharePooling\Repositories\SharePoolingMessageRepository;
use App\Aggregates\SharePooling\Repositories\SharePoolingRepository;
use Domain\Aggregates\SharePooling\Reactors\SharePoolingReactor;
use Domain\Aggregates\SharePooling\Repositories\SharePoolingMessageRepository as SharePoolingMessageRepositoryInterface;
use Domain\Aggregates\SharePooling\Repositories\SharePoolingRepository as SharePoolingRepositoryInterface;
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

class SharePoolingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // @phpstan-ignore-next-line
        $classNameInflector = new ExplicitlyMappedClassNameInflector(config('eventsourcing.class_map'));

        $this->app->bind(SharePoolingMessageRepositoryInterface::class, fn (Application $app) => new SharePoolingMessageRepository(
            // @phpstan-ignore-next-line
            connection: $app->make(DatabaseManager::class)->connection(),
            tableName: 'share_pooling_events',
            serializer: new ConstructingMessageSerializer(
                $classNameInflector,
                new PayloadSerializerSupportingObjectMapperAndSerializablePayload(),
            ),
            uuidEncoder: new StringUuidEncoder(),
        ));

        $this->app->bind(SharePoolingRepositoryInterface::class, fn () => new SharePoolingRepository(
            // @phpstan-ignore-next-line
            $this->app->make(SharePoolingMessageRepositoryInterface::class),
            // @phpstan-ignore-next-line
            new MessageDispatcherChain(new SynchronousMessageDispatcher($this->app->make(SharePoolingReactor::class))),
            new MessageDecoratorChain(new DefaultHeadersDecorator($classNameInflector)),
            $classNameInflector,
        ));
    }
}
