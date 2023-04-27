<?php

namespace Domain\Tests\Aggregates\NonFungibleAsset\Reactors;

use Domain\Aggregates\NonFungibleAsset\Reactors\NonFungibleAssetReactor;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\Services\ActionRunner\ActionRunner;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Mockery;
use Mockery\MockInterface;

class NonFungibleAssetReactorTestCase extends MessageConsumerTestCase
{
    protected $aggregateRootId;

    protected MockInterface $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateRootId = NonFungibleAssetId::generate();
    }

    public function messageConsumer(): MessageConsumer
    {
        $this->runner = Mockery::spy(ActionRunner::class);

        return new NonFungibleAssetReactor($this->runner);
    }
}
