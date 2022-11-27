<?php

namespace Domain\Tests\TaxYear;

use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RecordCapitalLoss;
use Domain\TaxYear\Actions\RecordIncome;
use Domain\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\TaxYear\Actions\RevertCapitalGain;
use Domain\TaxYear\Actions\RevertCapitalLoss;
use Domain\TaxYear\TaxYear;
use Domain\TaxYear\TaxYearId;
use Domain\Tests\AggregateRootTestCase;
use EventSauce\EventSourcing\AggregateRootId;

abstract class TaxYearTestCase extends AggregateRootTestCase
{
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
