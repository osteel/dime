<?php

declare(strict_types=1);

namespace Domain\TaxYear;

use Domain\Enums\FiatCurrency;
use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RecordCapitalLoss;
use Domain\TaxYear\Actions\RecordIncome;
use Domain\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\TaxYear\Actions\RevertCapitalGain;
use Domain\TaxYear\Actions\RevertCapitalLoss;
use Domain\TaxYear\Events\CapitalGainRecorded;
use Domain\TaxYear\Events\CapitalGainReverted;
use Domain\TaxYear\Events\CapitalLossRecorded;
use Domain\TaxYear\Events\CapitalLossReverted;
use Domain\TaxYear\Events\IncomeRecorded;
use Domain\TaxYear\Events\NonAttributableAllowableCostRecorded;
use Domain\TaxYear\Exceptions\TaxYearException;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

class TaxYear implements AggregateRoot
{
    use AggregateRootBehaviour;

    private ?FiatCurrency $currency = null;
    private ?FiatAmount $capitalGainOrLoss = null;
    private ?FiatAmount $income = null;
    private ?FiatAmount $nonAttributableAllowableCosts = null;

    public function recordCapitalGain(RecordCapitalGain $action): void
    {
        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRecordCapitalGainForDifferentCurrency(
                taxYearId: $action->taxYearId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalGainRecorded(taxYearId: $action->taxYearId, amount: $action->amount));
    }

    public function applyCapitalGainRecorded(CapitalGainRecorded $event): void
    {
        $this->currency ??= $event->amount->currency;
        $this->capitalGainOrLoss = $this->capitalGainOrLoss?->plus($event->amount) ?? $event->amount;
    }

    public function revertCapitalGain(RevertCapitalGain $action): void
    {
        if (is_null($this->capitalGainOrLoss)) {
            throw TaxYearException::cannotRevertCapitalGainBeforeCapitalGainIsRecorded(taxYearId: $action->taxYearId);
        }

        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRevertCapitalGainFromDifferentCurrency(
                taxYearId: $action->taxYearId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalGainReverted(taxYearId: $action->taxYearId, amount: $action->amount));
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
                taxYearId: $action->taxYearId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalLossRecorded(taxYearId: $action->taxYearId, amount: $action->amount));
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
            throw TaxYearException::cannotRevertCapitalLossBeforeCapitalLossIsRecorded(taxYearId: $action->taxYearId);
        }

        if ($this->currency && $this->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRevertCapitalLossFromDifferentCurrency(
                taxYearId: $action->taxYearId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalLossReverted(taxYearId: $action->taxYearId, amount: $action->amount));
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
                taxYearId: $action->taxYearId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new IncomeRecorded(taxYearId: $action->taxYearId, amount: $action->amount));
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
                taxYearId: $action->taxYearId,
                from: $this->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new NonAttributableAllowableCostRecorded(taxYearId: $action->taxYearId, amount: $action->amount));
    }

    public function applyNonAttributableAllowableCostRecorded(NonAttributableAllowableCostRecorded $event): void
    {
        $this->currency ??= $event->amount->currency;
        $this->nonAttributableAllowableCosts = $this->nonAttributableAllowableCosts?->plus($event->amount) ?? $event->amount;
    }
}
