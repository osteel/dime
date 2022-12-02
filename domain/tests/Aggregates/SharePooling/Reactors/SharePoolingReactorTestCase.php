<?php

namespace Domain\Tests\Aggregates\SharePooling\Reactors;

use Domain\Aggregates\SharePooling\Reactors\SharePoolingReactor;
use Domain\Aggregates\SharePooling\SharePoolingId;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Mockery;
use Mockery\MockInterface;

class SharePoolingReactorTestCase extends MessageConsumerTestCase
{
    protected $aggregateRootId;
    protected MockInterface $taxYearRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateRootId = SharePoolingId::generate();
    }

    public function messageConsumer(): MessageConsumer
    {
        $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);

        return new SharePoolingReactor($this->taxYearRepository);
    }
}
