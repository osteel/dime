<?php

namespace Domain\Tests\SharePooling\Reactors;

use Domain\SharePooling\Reactors\SharePoolingReactor;
use Domain\SharePooling\SharePoolingId;
use Domain\TaxYear\Repositories\TaxYearRepository;
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
