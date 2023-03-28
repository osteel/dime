<?php

namespace Domain\Tests\Aggregates\NonFungibleAsset\Reactors;

use Domain\Aggregates\NonFungibleAsset\NonFungibleAssetId;
use Domain\Aggregates\NonFungibleAsset\Reactors\NonFungibleAssetReactor;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Mockery;
use Mockery\MockInterface;

class NonFungibleAssetReactorTestCase extends MessageConsumerTestCase
{
    protected $aggregateRootId;
    protected MockInterface $taxYearRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateRootId = NonFungibleAssetId::generate();
    }

    public function messageConsumer(): MessageConsumer
    {
        $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);

        return new NonFungibleAssetReactor($this->taxYearRepository);
    }
}
