<?php

namespace Domain\Tests\Aggregates\SharePoolingAsset\Reactors;

use Domain\Aggregates\SharePoolingAsset\Reactors\SharePoolingAssetReactor;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAssetId;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Mockery;
use Mockery\MockInterface;

class SharePoolingAssetReactorTestCase extends MessageConsumerTestCase
{
    protected $aggregateRootId;
    protected MockInterface $taxYearRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateRootId = SharePoolingAssetId::generate();
    }

    public function messageConsumer(): MessageConsumer
    {
        $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);

        return new SharePoolingAssetReactor($this->taxYearRepository);
    }
}
