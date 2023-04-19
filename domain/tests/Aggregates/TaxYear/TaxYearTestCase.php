<?php

namespace Domain\Tests\Aggregates\TaxYear;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Tests\AggregateRootTestCase;
use EventSauce\EventSourcing\AggregateRootId;

abstract class TaxYearTestCase extends AggregateRootTestCase
{
    protected string $taxYear = '2015-2016';

    protected function newAggregateRootId(): AggregateRootId
    {
        return TaxYearId::fromDate(LocalDate::parse('2015-10-21'));
    }

    protected function aggregateRootClassName(): string
    {
        return TaxYear::class;
    }

    public function handle(object $action)
    {
        $taxYear = $this->repository->retrieve($this->aggregateRootId);

        match ($action::class) {
            UpdateCapitalGain::class => $taxYear->updateCapitalGain($action),
            RevertCapitalGainUpdate::class => $taxYear->revertCapitalGainUpdate($action),
            UpdateIncome::class => $taxYear->updateIncome($action),
            UpdateNonAttributableAllowableCost::class => $taxYear->updateNonAttributableAllowableCost($action),
        };

        $this->repository->persist($taxYear);
    }
}
