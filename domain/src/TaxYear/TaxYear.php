<?php

declare(strict_types=1);

namespace Domain\TaxYear;

use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RevertCapitalGain;
use Domain\TaxYear\Events\CapitalGainRecorded;
use Domain\TaxYear\Events\CapitalGainReverted;
use Domain\TaxYear\Exceptions\TaxYearException;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

final class TaxYear implements AggregateRoot
{
    use AggregateRootBehaviour;

    private ?FiatAmount $capitalGain = null;

    public function recordCapitalGain(RecordCapitalGain $action): void
    {
        if ($this->capitalGain && $this->capitalGain->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRecordCapitalGainForDifferentCurrency(
                taxYearId: $action->taxYearId,
                from: $this->capitalGain->currency,
                to: $action->amount->currency,
            );
        }

        $this->recordThat(new CapitalGainRecorded(taxYearId: $action->taxYearId, amount: $action->amount));
    }

    public function applyCapitalGainRecorded(CapitalGainRecorded $event): void
    {
        $this->capitalGain = $this->capitalGain?->plus($event->amount) ?? $event->amount;
    }

    public function revertCapitalGain(RevertCapitalGain $action): void
    {
        if (is_null($this->capitalGain)) {
            throw TaxYearException::cannotRevertCapitalGainBeforeCapitalGainIsRecorded(taxYearId: $action->taxYearId);
        }

        if ($this->capitalGain->currency !== $action->amount->currency) {
            throw TaxYearException::cannotRevertCapitalGainFromDifferentCurrency(
                taxYearId: $action->taxYearId,
                from: $this->capitalGain->currency,
                to: $action->amount->currency,
            );
        }

        if ($this->capitalGain->isLessThan($action->amount)) {
            throw TaxYearException::cannotRevertCapitalGainBecauseAmountIsTooHigh(
                taxYearId: $action->taxYearId,
                amountToRevert: $action->amount,
                availableAmount: $this->capitalGain,
            );
        }

        $this->recordThat(new CapitalGainReverted(taxYearId: $action->taxYearId, amount: $action->amount));
    }

    public function applyCapitalGainReverted(CapitalGainReverted $event): void
    {
        $this->capitalGain = $this->capitalGain->minus($event->amount);
    }
}
