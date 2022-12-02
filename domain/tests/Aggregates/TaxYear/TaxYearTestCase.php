<?php

namespace Domain\Tests\Aggregates\TaxYear;

use Domain\Aggregates\TaxYear\Actions\RecordCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalLoss;
use Domain\Aggregates\TaxYear\Actions\RecordIncome;
use Domain\Aggregates\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalLoss;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Tests\AggregateRootTestCase;
use EventSauce\EventSourcing\AggregateRootId;

abstract class TaxYearTestCase extends AggregateRootTestCase
{
    protected string $taxYear = '2015-2016';

    protected function newAggregateRootId(): AggregateRootId
    {
        return TaxYearId::generate();
    }

    protected function aggregateRootClassName(): string
    {
        return TaxYear::class;
    }

    public function handle(object $action)
    {
        $taxYear = $this->repository->retrieve($this->aggregateRootId);

        match ($action::class) {
            RecordCapitalGain::class => $taxYear->recordCapitalGain($action),
            RevertCapitalGain::class => $taxYear->revertCapitalGain($action),
            RecordCapitalLoss::class => $taxYear->recordCapitalLoss($action),
            RevertCapitalLoss::class => $taxYear->revertCapitalLoss($action),
            RecordIncome::class => $taxYear->recordIncome($action),
            RecordNonAttributableAllowableCost::class => $taxYear->recordNonAttributableAllowableCost($action),
        };

        $this->repository->persist($taxYear);
    }
}
