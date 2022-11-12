<?php

namespace Domain\SharePooling;

use Domain\Enums\FiatCurrency;
use Domain\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\SharePooling\Actions\RevertSharePoolingTokenDisposal;
use Domain\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\SharePooling\Exceptions\SharePoolingException;
use Domain\SharePooling\Services\QuantityAdjuster;
use Domain\SharePooling\Services\SharePoolingTokenDisposalBuilder;
use Domain\SharePooling\Services\SharePoolingTransactionFinder;
use Domain\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
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

        $disposalsToRevert = SharePoolingTransactionFinder::getDisposalsToRevertAfterAcquisition(
            transactions: $this->transactions,
            date: $action->date,
            quantity: $action->quantity,
        );

        // Revert the disposals first
        foreach ($disposalsToRevert as $disposal) {
            $this->revertDisposal(new RevertSharePoolingTokenDisposal(
                sharePoolingId: $action->sharePoolingId,
                sharePoolingTokenDisposal: $disposal,
            ));
        }

        // Record the new acquisition
        $this->recordThat(new SharePoolingTokenAcquired(
            sharePoolingId: $action->sharePoolingId,
            sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
                date: $action->date,
                quantity: $action->quantity,
                costBasis: $action->costBasis,
            ),
        ));

        // Replay the disposals
        foreach ($disposalsToRevert as $disposal) {
            $this->disposeOf(new DisposeOfSharePoolingToken(
                sharePoolingId: $action->sharePoolingId,
                date: $disposal->date,
                quantity: $disposal->quantity,
                disposalProceeds: $disposal->disposalProceeds,
                position: $disposal->getPosition(),
            ));
        }
    }

    public function applySharePoolingTokenAcquired(SharePoolingTokenAcquired $event): void
    {
        $this->fiatCurrency ??= $event->sharePoolingTokenAcquisition->costBasis->currency;

        $this->transactions->add($event->sharePoolingTokenAcquisition);
    }

    private function revertDisposal(RevertSharePoolingTokenDisposal $action): void
    {
        // Restore quantities deducted from the acquisitions that the disposal was initially matched with
        QuantityAdjuster::restoreAcquisitionQuantities($action->sharePoolingTokenDisposal, $this->transactions);

        $this->recordThat(new SharePoolingTokenDisposalReverted(
            sharePoolingId: $action->sharePoolingId,
            sharePoolingTokenDisposal: $action->sharePoolingTokenDisposal,
        ));
    }

    public function applySharePoolingTokenDisposalReverted(SharePoolingTokenDisposalReverted $event): void
    {
        // Replace the disposal in the array with the same disposal, but with reset quantities. This
        // way, when several disposals are being replayed, a disposal won't be matched with future
        // acquisitions within the next 30 days if these acquisitions have disposals on the same day
        $this->transactions->add($event->sharePoolingTokenDisposal->copyAsUnprocessed());
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

        // We check the absolute available quantity up to and including the disposal's
        // date, excluding potential reverted disposals made later on that day
        $availableQuantity = $this->transactions->madeBeforeOrOn($action->date)->processed()->quantity();

        if ($action->quantity->isGreaterThan($availableQuantity)) {
            throw SharePoolingException::insufficientQuantity(
                sharePoolingId: $action->sharePoolingId,
                disposalQuantity: $action->quantity,
                availableQuantity: $availableQuantity,
            );
        }

        // Revert processed disposals whose 30-day quantity was matched with acquisitions on the same day as
        // the current disposal, whose same-day quantity is about to be matched with the current disposal
        // @TODO move this to SharePoolingTransactionFinder?
        $disposalsToRevert = SharePoolingTokenDisposals::make();
        $sameDayAcquisitions = $this->transactions->acquisitionsMadeOn($action->date)->withThirtyDayQuantity();
        foreach ($sameDayAcquisitions as $acquisition) {
            $disposalsToRevert->add(...$this->transactions->processed()->disposalsWithThirtyDayQuantityMatchedWith($acquisition));
        }

        // If there are no disposals to revert, process the current disposal normally and record the event
        if ($disposalsToRevert->isEmpty()) {
            $sharePoolingTokenDisposal = SharePoolingTokenDisposalBuilder::make(
                transactions: $this->transactions,
                date: $action->date,
                quantity: $action->quantity,
                disposalProceeds: $action->disposalProceeds,
                position: $action->position,
            );

            $this->recordThat(new SharePoolingTokenDisposedOf(
                sharePoolingId: $action->sharePoolingId,
                sharePoolingTokenDisposal: $sharePoolingTokenDisposal,
            ));

            return;
        }

        // Revert the disposals
        foreach ($disposalsToRevert as $disposal) {
            $this->revertDisposal(new RevertSharePoolingTokenDisposal(
                sharePoolingId: $action->sharePoolingId,
                sharePoolingTokenDisposal: $disposal,
            ));
        }

        // Add the current disposal to the transactions (as unprocessed) so previous disposals
        // don't try to match their 30-day quantity with the disposal's same-day acquisitions
        $this->transactions->add($disposal = new SharePoolingTokenDisposal(
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $action->disposalProceeds->nilAmount(),
            disposalProceeds: $action->disposalProceeds,
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
            processed: false,
        ));

        // Replay the disposals, processing the current disposal last
        $disposalsToRevert->add($disposal);
        foreach ($disposalsToRevert as $disposal) {
            $this->disposeOf(new DisposeOfSharePoolingToken(
                sharePoolingId: $action->sharePoolingId,
                date: $disposal->date,
                quantity: $disposal->quantity,
                disposalProceeds: $disposal->disposalProceeds,
                position: $disposal->getPosition(),
            ));
        }
    }

    public function applySharePoolingTokenDisposedOf(SharePoolingTokenDisposedOf $event): void
    {
        $this->transactions->add($event->sharePoolingTokenDisposal);
    }
}
