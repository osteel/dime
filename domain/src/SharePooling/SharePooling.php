<?php

namespace Domain\SharePooling;

use Domain\Enums\FiatCurrency;
use Domain\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\SharePooling\Exceptions\SharePoolingException;
use Domain\SharePooling\Services\DisposalCostBasisCalculator;
use Domain\SharePooling\ValueObjects\SharePoolingAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\Services\Math\Math;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;

final class SharePooling implements AggregateRoot
{
    use AggregateRootBehaviour;

    private ?FiatCurrency $fiatCurrency = null;
    private SharePoolingTransactions $transactions;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = $aggregateRootId;

        $this->transactions = SharePoolingTransactions::make();
    }

    /** @throws SharePoolingException */
    public function acquire(AcquireSharePoolingToken $action): void
    {
        if ($this->fiatCurrency && $this->fiatCurrency !== $action->costBasis->currency) {
            throw SharePoolingException::cannotAcquireFromDifferentFiatCurrency(
                sharePoolingId: $action->sharePoolingId,
                from: $this->fiatCurrency,
                to: $action->costBasis->currency,
            );
        }

        // Go through disposals in the past 30 days FIFO
        $past30DaysDisposals = $this->transactions->disposalsMadeBetween($action->date->minusDays(30), $action->date);
        $disposalsToReplay = [];

        foreach ($past30DaysDisposals as $disposal) {
            // Revert the ones whose quantities are not covered by the acquisitions
            // made in their next 30 days (including the disposal's date)
            $subsequentAcquisitions = $this->transactions->acquisitionsMadeBetween($disposal->date, $disposal->date->plusDays(30));
            if (Math::lt($disposal->quantity, $subsequentAcquisitions->quantity())) {
                $this->recordThat(new SharePoolingTokenDisposalReverted(
                    sharePoolingId: $disposal->sharePoolingId,
                    date: $disposal->date,
                    quantity: $disposal->quantity,
                    costBasis: $disposal->costBasis,
                ));
                $disposalsToReplay[] = $disposal;
            }
            // Stop as soon as a disposal had its entire quantity covered by future acquisitions
        }

        $this->recordThat(new SharePoolingTokenAcquired(
            sharePoolingId: $action->sharePoolingId,
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $action->costBasis,
        ));

        // Replay original disposal events
        foreach (array_reverse($disposalsToReplay) as $disposal) {
            $this->disposeOf(new DisposeOfSharePoolingToken(
                sharePoolingId: $disposal->sharePoolingId,
                date: $disposal->date,
                quantity: $disposal->quantity,
                disposalProceeds: $disposal->disposalProceeds,
            ));
        }
    }

    public function applySharePoolingTokenAcquired(SharePoolingTokenAcquired $event): void
    {
        $this->fiatCurrency ??= $event->costBasis->currency;

        // @TODO A service should update past transactions and calculate the quantity that goes to the section 104 pool

        $this->transactions->add(new SharePoolingAcquisition(
            date: $event->date,
            quantity: $event->quantity,
            costBasis: $event->costBasis,
            section104Quantity: ,
        ));
    }

    public function applySharePoolingTokenDisposalReverted(SharePoolingTokenDisposalReverted $event): void
    {
    }

    /** @throws SharePoolingException */
    public function disposeOf(DisposeOfSharePoolingToken $action): void
    {
        if ($this->fiatCurrency && $this->fiatCurrency !== $action->disposalProceeds->currency) {
            throw SharePoolingException::cannotDisposeOfFromDifferentFiatCurrency(
                sharePoolingId: $action->sharePoolingId,
                from: $this->fiatCurrency,
                to: $action->disposalProceeds->currency,
            );
        }

        // @TODO is that correct? Or should it be the sum of the section 104 pool quantities instead?
        $availableQuantity = $this->transactions->quantity();

        if (Math::gt($action->quantity, $availableQuantity)) {
            throw SharePoolingException::insufficientQuantityAvailable(
                sharePoolingId: $action->sharePoolingId,
                disposalQuantity: $action->quantity,
                availableQuantity: $availableQuantity,
            );
        }

        $costBasis = DisposalCostBasisCalculator::calculate(
            action: $action,
            transactions: $this->transactions->copy(),
        );

        $this->recordThat(new SharePoolingTokenDisposedOf(
            sharePoolingId: $action->sharePoolingId,
            date: $action->date,
            quantity: $action->quantity,
            disposalProceeds: $action->disposalProceeds,
            costBasis: $costBasis,
        ));
    }

    public function applySharePoolingTokenDisposedOf(SharePoolingTokenDisposedOf $event): void
    {
        $this->transactions->add(new SharePoolingDisposal(
            date: $event->date,
            quantity: $event->quantity,
            costBasis: $event->costBasis,
            disposalProceeds: $event->disposalProceeds,
        ));
    }
}
