<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear;

use Domain\Enums\FiatCurrency;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\Exceptions\TaxYearException;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;

/**
 * @implements AggregateRoot<TaxYearId>
 * @property TaxYearId $aggregateRootId
 */
class TaxYear implements AggregateRoot
{
    /** @phpstan-use AggregateRootBehaviour<TaxYearId> */
    use AggregateRootBehaviour;

    private ?FiatCurrency $currency = null;
    private ?FiatAmount $capitalGain = null;
    private ?FiatAmount $income = null;
    private ?FiatAmount $nonAttributableAllowableCost = null;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = TaxYearId::fromString($aggregateRootId->toString());
    }

    public function updateCapitalGain(UpdateCapitalGain $action): void
    {
        if ($this->currencyMismatch($action->capitalGain->currency())) {
            throw TaxYearException::cannotUpdateCapitalGainFromDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->capitalGain->currency(),
            );
        }

        $this->recordThat(new CapitalGainUpdated(
            taxYear: $action->taxYear,
            date: $action->date,
            capitalGain: $action->capitalGain,
        ));
    }

    public function applyCapitalGainUpdated(CapitalGainUpdated $event): void
    {
        $this->currency ??= $event->capitalGain->currency();
        $this->capitalGain = $this->capitalGain?->plus($event->capitalGain->difference) ?? $event->capitalGain->difference;
    }

    public function revertCapitalGainUpdate(RevertCapitalGainUpdate $action): void
    {
        if (is_null($this->capitalGain)) {
            throw TaxYearException::cannotRevertCapitalGainUpdateBeforeCapitalGainIsUpdated(taxYearId: $this->aggregateRootId);
        }

        if ($this->currencyMismatch($action->capitalGain->currency())) {
            throw TaxYearException::cannotRevertCapitalGainUpdateFromDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->capitalGain->currency(),
            );
        }

        $this->recordThat(new CapitalGainUpdateReverted(
            taxYear: $action->taxYear,
            date: $action->date,
            capitalGain: $action->capitalGain,
        ));
    }

    public function applyCapitalGainUpdateReverted(CapitalGainUpdateReverted $event): void
    {
        assert(! is_null($this->capitalGain));

        $this->capitalGain = $this->capitalGain->minus($event->capitalGain->difference);
    }

    public function updateIncome(UpdateIncome $action): void
    {
        if ($this->currencyMismatch($action->income->currency)) {
            throw TaxYearException::cannotUpdateIncomeFromDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->income->currency,
            );
        }

        $this->recordThat(new IncomeUpdated(
            taxYear: $action->taxYear,
            date: $action->date,
            income: $action->income,
        ));
    }

    public function applyIncomeUpdated(IncomeUpdated $event): void
    {
        $this->currency ??= $event->income->currency;
        $this->income = $this->income?->plus($event->income) ?? $event->income;
    }

    public function updateNonAttributableAllowableCost(UpdateNonAttributableAllowableCost $action): void
    {
        if ($this->currencyMismatch($action->nonAttributableAllowableCost->currency)) {
            throw TaxYearException::cannotUpdateNonAttributableAllowableCostFromDifferentCurrency(
                taxYearId: $this->aggregateRootId,
                from: $this->currency,
                to: $action->nonAttributableAllowableCost->currency,
            );
        }

        $this->recordThat(new NonAttributableAllowableCostUpdated(
            taxYear: $action->taxYear,
            date: $action->date,
            nonAttributableAllowableCost: $action->nonAttributableAllowableCost,
        ));
    }

    public function applyNonAttributableAllowableCostUpdated(NonAttributableAllowableCostUpdated $event): void
    {
        $this->currency ??= $event->nonAttributableAllowableCost->currency;
        $this->nonAttributableAllowableCost = $this->nonAttributableAllowableCost?->plus($event->nonAttributableAllowableCost)
            ?? $event->nonAttributableAllowableCost;
    }

    private function currencyMismatch(FiatCurrency $incoming): bool
    {
        return $this->currency && $this->currency !== $incoming;
    }
}
