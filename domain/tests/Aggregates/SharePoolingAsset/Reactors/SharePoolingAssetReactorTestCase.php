<?php

namespace Domain\Tests\Aggregates\SharePoolingAsset\Reactors;

use Domain\Aggregates\SharePoolingAsset\Reactors\SharePoolingAssetReactor;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Illuminate\Contracts\Bus\Dispatcher;
use Mockery;
use Mockery\MockInterface;

class SharePoolingAssetReactorTestCase extends MessageConsumerTestCase
{
    protected $aggregateRootId;
    protected MockInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateRootId = SharePoolingAssetId::generate();
    }

    public function messageConsumer(): MessageConsumer
    {
        $this->dispatcher = Mockery::spy(Dispatcher::class);

        return new SharePoolingAssetReactor($this->dispatcher);
    }
}
