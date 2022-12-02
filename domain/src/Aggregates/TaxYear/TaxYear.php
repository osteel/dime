<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear;

use Domain\Enums\FiatCurrency;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalLoss;
use Domain\Aggregates\TaxYear\Actions\RecordIncome;
use Domain\Aggregates\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalLoss;
use Domain\Aggregates\TaxYear\Events\CapitalGainRecorded;
use Domain\Aggregates\TaxYear\Events\CapitalGainReverted;
use Domain\Aggregates\TaxYear\Events\CapitalLossRecorded;
use Domain\Aggregates\TaxYear\Events\CapitalLossReverted;
use Domain\Aggregates\TaxYear\Events\IncomeRecorded;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostRecorded;
use Domain\Aggregates\TaxYear\Exceptions\TaxYearException;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;

/** @property TaxYearId $aggregateRootId */
class TaxYear implements AggregateRoot
{
    use AggregateRootBehaviour;

    private ?FiatCurrency $currency = null;
    private ?FiatAmount $capitalGainOrLoss = null;
    private ?FiatAmount $income = null;
    private ?FiatAmount $nonAttributableAllowableCosts = null;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = TaxYearId::fromString($aggregateRootId->toString());
    }

    public function recordCapitalGain(RecordCapitalGain $action): void
    {
        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRecordCapitalGainForDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalGainRecorded(
            taxYear: $action->taxYear,
            amount: $action->amount,
        ));
    }

    public function applyCapitalGainRecorded(CapitalGainRecorded $event): void
    {
        $this->currency ??= $event->amount->currency;
        $this->capitalGainOrLoss = $this->capitalGainOrLoss?->plus($event->amount) ?? $event->amount;
    }

    public function revertCapitalGain(RevertCapitalGain $action): void
    {
        if (is_null($this->capitalGainOrLoss)) {
            throw TaxYearException::cannotRevertCapitalGainBeforeCapitalGainIsRecorded(taxYearId: $this->aggregateRootId);
        }

        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRevertCapitalGainFromDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalGainReverted(
            taxYear: $action->taxYear,
            amount: $action->amount,
        ));
    }

    public function applyCapitalGainReverted(CapitalGainReverted $event): void
    {
        assert(! is_null($this->capitalGainOrLoss));

        $this->capitalGainOrLoss = $this->capitalGainOrLoss->minus($event->amount);
    }

    public function recordCapitalLoss(RecordCapitalLoss $action): void
    {
        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRecordCapitalLossForDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalLossRecorded(
            taxYear: $action->taxYear,
            amount: $action->amount,
        ));
    }

    public function applyCapitalLossRecorded(CapitalLossRecorded $event): void
    {
        $this->currency ??= $event->amount->currency;
        $this->capitalGainOrLoss = $this->capitalGainOrLoss?->minus($event->amount)
            ?? $event->amount->nilAmount()->minus($event->amount);
    }

    public function revertCapitalLoss(RevertCapitalLoss $action): void
    {
        if (is_null($this->capitalGainOrLoss)) {
            throw TaxYearException::cannotRevertCapitalLossBeforeCapitalLossIsRecorded(taxYearId: $this->aggregateRootId);
        }

        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRevertCapitalLossFromDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalLossReverted(
            taxYear: $action->taxYear,
            amount: $action->amount,
        ));
    }

    public function applyCapitalLossReverted(CapitalLossReverted $event): void
    {
        assert(! is_null($this->capitalGainOrLoss));

        $this->capitalGainOrLoss = $this->capitalGainOrLoss->plus($event->amount);
    }

    public function recordIncome(RecordIncome $action): void
    {
        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRecordIncomeFromDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new IncomeRecorded(
            taxYear: $action->taxYear,
            amount: $action->amount,
        ));
    }

    public function applyIncomeRecorded(IncomeRecorded $event): void
    {
        $this->currency ??= $event->amount->currency;
        $this->income = $this->income?->plus($event->amount) ?? $event->amount;
    }

    public function recordNonAttributableAllowableCost(RecordNonAttributableAllowableCost $action): void
    {
        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRecordNonAttributableAllowableCostFromDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new NonAttributableAllowableCostRecorded(
            taxYear: $action->taxYear,
            amount: $action->amount,
        ));
    }

    public function applyNonAttributableAllowableCostRecorded(NonAttributableAllowableCostRecorded $event): void
    {
        $this->currency ??= $event->amount->currency;
        $this->nonAttributableAllowableCosts = $this->nonAttributableAllowableCosts?->plus($event->amount) ?? $event->amount;
    }
}
