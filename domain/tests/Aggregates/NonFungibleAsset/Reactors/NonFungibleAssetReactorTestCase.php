<?php

namespace Domain\Tests\Aggregates\NonFungibleAsset\Reactors;

use Domain\Aggregates\NonFungibleAsset\Reactors\NonFungibleAssetReactor;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Illuminate\Contracts\Bus\Dispatcher;
use Mockery;
use Mockery\MockInterface;

class NonFungibleAssetReactorTestCase extends MessageConsumerTestCase
{
    protected $aggregateRootId;
    protected MockInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateRootId = NonFungibleAssetId::generate();
    }

    public function messageConsumer(): MessageConsumer
    {
        $this->dispatcher = Mockery::spy(Dispatcher::class);

        return new NonFungibleAssetReactor($this->dispatcher);
    }
}
