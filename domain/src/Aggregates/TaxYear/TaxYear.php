<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\Exceptions\TaxYearException;
use Domain\Aggregates\TaxYear\Services\TaxYearNormaliser\TaxYearNormaliser;
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

    private ?FiatAmount $capitalGain = null;

    private ?FiatAmount $income = null;

    private ?FiatAmount $nonAttributableAllowableCost = null;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = TaxYearId::fromString($aggregateRootId->toString());
    }

    /** @throws TaxYearException */
    public function updateCapitalGain(UpdateCapitalGain $action): void
    {
        $this->checkTaxYear($action->date, $action);
        $this->checkCurrency($action->capitalGain->currency(), $action);

        $this->recordThat(new CapitalGainUpdated(
            taxYear: TaxYearNormaliser::fromDate($action->date),
            date: $action->date,
            capitalGain: $action->capitalGain,
        ));
    }

    public function applyCapitalGainUpdated(CapitalGainUpdated $event): void
    {
        $this->currency ??= $event->capitalGain->currency();
        $this->capitalGain = $this->capitalGain?->plus($event->capitalGain->difference) ?? $event->capitalGain->difference;
    }

    /** @throws TaxYearException */
    public function revertCapitalGainUpdate(RevertCapitalGainUpdate $action): void
    {
        $this->checkTaxYear($action->date, $action);

        if (is_null($this->capitalGain)) {
            throw TaxYearException::cannotRevertCapitalGainUpdateBeforeCapitalGainIsUpdated(taxYearId: $this->aggregateRootId);
        }

        $this->checkCurrency($action->capitalGain->currency(), $action);

        $this->recordThat(new CapitalGainUpdateReverted(
            taxYear: TaxYearNormaliser::fromDate($action->date),
            date: $action->date,
            capitalGain: $action->capitalGain,
        ));
    }

    public function applyCapitalGainUpdateReverted(CapitalGainUpdateReverted $event): void
    {
        assert(! is_null($this->capitalGain));

        $this->capitalGain = $this->capitalGain->minus($event->capitalGain->difference);
    }

    /** @throws TaxYearException */
    public function updateIncome(UpdateIncome $action): void
    {
        $this->checkTaxYear($action->date, $action);
        $this->checkCurrency($action->income->currency, $action);

        $this->recordThat(new IncomeUpdated(
            taxYear: TaxYearNormaliser::fromDate($action->date),
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
        $this->checkTaxYear($action->date, $action);
        $this->checkCurrency($action->nonAttributableAllowableCost->currency, $action);

        $this->recordThat(new NonAttributableAllowableCostUpdated(
            taxYear: TaxYearNormaliser::fromDate($action->date),
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
    private function checkTaxYear(LocalDate $date, Stringable $action): void
    {
        if (TaxYearId::fromDate($date)->toString() === (string) $this->aggregateRootId->toString()) {
            return;
        }

        throw TaxYearException::taxYearMismatch(
            taxYearId: $this->aggregateRootId,
            action: $action,
            incomingTaxYear: TaxYearNormaliser::fromDate($date),
        );
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
