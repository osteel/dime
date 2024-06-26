<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear;

use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\Exceptions\TaxYearException;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use Stringable;

/** @property TaxYearId $aggregateRootId */
final class TaxYear implements TaxYearContract
{
    /** @phpstan-use AggregateRootBehaviour<TaxYearId> */
    use AggregateRootBehaviour;

    private ?FiatCurrency $currency = null;

    private ?CapitalGain $capitalGain = null;

    private ?FiatAmount $income = null;

    private ?FiatAmount $nonAttributableAllowableCost = null;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = TaxYearId::fromString($aggregateRootId->toString());
    }

    /** @throws TaxYearException */
    public function updateCapitalGain(UpdateCapitalGain $action): void
    {
        $this->checkCurrency($action->capitalGainUpdate->currency(), $action);

        $this->recordThat(new CapitalGainUpdated(
            date: $action->date,
            capitalGainUpdate: $action->capitalGainUpdate,
            newCapitalGain: $this->capitalGain?->plus($action->capitalGainUpdate) ?? $action->capitalGainUpdate,
        ));
    }

    public function applyCapitalGainUpdated(CapitalGainUpdated $event): void
    {
        $this->currency ??= $event->capitalGainUpdate->currency();
        $this->capitalGain = $event->newCapitalGain;
    }

    /** @throws TaxYearException */
    public function revertCapitalGainUpdate(RevertCapitalGainUpdate $action): void
    {
        if (is_null($this->capitalGain)) {
            throw TaxYearException::cannotRevertCapitalGainUpdateBeforeCapitalGainIsUpdated(taxYearId: $this->aggregateRootId);
        }

        $this->checkCurrency($action->capitalGainUpdate->currency(), $action);

        $this->recordThat(new CapitalGainUpdateReverted(
            date: $action->date,
            capitalGainUpdate: $action->capitalGainUpdate,
            newCapitalGain: $this->capitalGain?->minus($action->capitalGainUpdate) ?? $action->capitalGainUpdate,
        ));
    }

    public function applyCapitalGainUpdateReverted(CapitalGainUpdateReverted $event): void
    {
        $this->capitalGain = $event->newCapitalGain;
    }

    /** @throws TaxYearException */
    public function updateIncome(UpdateIncome $action): void
    {
        $this->checkCurrency($action->incomeUpdate->currency, $action);

        $this->recordThat(new IncomeUpdated(
            date: $action->date,
            incomeUpdate: $action->incomeUpdate,
            newIncome: $this->income?->plus($action->incomeUpdate) ?? $action->incomeUpdate,
        ));
    }

    public function applyIncomeUpdated(IncomeUpdated $event): void
    {
        $this->currency ??= $event->incomeUpdate->currency;
        $this->income = $event->newIncome;
    }

    /** @throws TaxYearException */
    public function updateNonAttributableAllowableCost(UpdateNonAttributableAllowableCost $action): void
    {
        $this->checkCurrency($action->nonAttributableAllowableCostChange->currency, $action);

        $this->recordThat(new NonAttributableAllowableCostUpdated(
            date: $action->date,
            nonAttributableAllowableCostChange: $action->nonAttributableAllowableCostChange,
            newNonAttributableAllowableCost: $this->nonAttributableAllowableCost?->plus($action->nonAttributableAllowableCostChange)
                ?? $action->nonAttributableAllowableCostChange,
        ));
    }

    public function applyNonAttributableAllowableCostUpdated(NonAttributableAllowableCostUpdated $event): void
    {
        $this->currency ??= $event->nonAttributableAllowableCostChange->currency;
        $this->nonAttributableAllowableCost = $event->newNonAttributableAllowableCost;
    }

    /** @throws TaxYearException */
    private function checkCurrency(FiatCurrency $incoming, Stringable $action): void
    {
        if (is_null($this->currency) || $this->currency === $incoming) {
            return;
        }

        throw TaxYearException::currencyMismatch(
            taxYearId: $this->aggregateRootId,
            action: $action,
            current: $this->currency,
            incoming: $incoming,
        );
    }
}
