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
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use Stringable;

/**
 * @implements AggregateRoot<TaxYearId>
 *
 * @property TaxYearId $aggregateRootId
 */
class TaxYear implements AggregateRoot
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

        $this->checkCurrency($action->capitalGain->currency(), $action);

        $this->recordThat(new CapitalGainUpdateReverted(
            date: $action->date,
            capitalGain: $action->capitalGain,
        ));
    }

    public function applyCapitalGainUpdateReverted(CapitalGainUpdateReverted $event): void
    {
        assert(! is_null($this->capitalGain));

        $this->capitalGain = $this->capitalGain->minus($event->capitalGain);
    }

    /** @throws TaxYearException */
    public function updateIncome(UpdateIncome $action): void
    {
        $this->checkCurrency($action->income->currency, $action);

        $this->recordThat(new IncomeUpdated(
            date: $action->date,
            income: $action->income,
        ));
    }

    public function applyIncomeUpdated(IncomeUpdated $event): void
    {
        $this->currency ??= $event->income->currency;
        $this->income = $this->income?->plus($event->income) ?? $event->income;
    }

    /** @throws TaxYearException */
    public function updateNonAttributableAllowableCost(UpdateNonAttributableAllowableCost $action): void
    {
        $this->checkCurrency($action->nonAttributableAllowableCost->currency, $action);

        $this->recordThat(new NonAttributableAllowableCostUpdated(
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
