<?php

namespace Domain\Tests\Aggregates\SharePoolingAsset\Reactors;

use Domain\Aggregates\SharePoolingAsset\Reactors\SharePoolingAssetReactor;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use Domain\Services\ActionRunner\ActionRunner;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Mockery;
use Mockery\MockInterface;

class SharePoolingAssetReactorTestCase extends MessageConsumerTestCase
{
    protected $aggregateRootId;

    protected MockInterface $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateRootId = SharePoolingAssetId::fromString('FOO');
    }

    public function messageConsumer(): MessageConsumer
    {
        $this->runner = Mockery::spy(ActionRunner::class);

        return new SharePoolingAssetReactor($this->runner);
    }
}
