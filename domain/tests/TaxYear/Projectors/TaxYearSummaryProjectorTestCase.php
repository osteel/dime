<?php

namespace Domain\Tests\TaxYear\Projectors;

use Domain\TaxYear\Projectors\TaxYearSummaryProjector;
use Domain\TaxYear\Repositories\TaxYearSummaryRepository;
use Domain\TaxYear\TaxYearId;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Mockery;
use Mockery\MockInterface;

class TaxYearSummaryProjectorTestCase extends MessageConsumerTestCase
{
    protected $aggregateRootId;
    protected MockInterface $taxYearSummaryRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateRootId = TaxYearId::generate();
    }

    public function messageConsumer(): MessageConsumer
    {
        $this->taxYearSummaryRepository = Mockery::spy(TaxYearSummaryRepository::class);

        return new TaxYearSummaryProjector($this->taxYearSummaryRepository);
    }
}
