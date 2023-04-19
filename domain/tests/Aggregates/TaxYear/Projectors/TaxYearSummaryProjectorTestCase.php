<?php

namespace Domain\Tests\Aggregates\TaxYear\Projectors;

use Domain\Aggregates\TaxYear\Projectors\TaxYearSummaryProjector;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Mockery;
use Mockery\MockInterface;

class TaxYearSummaryProjectorTestCase extends MessageConsumerTestCase
{
    protected string $taxYear = '2015-2016';

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
