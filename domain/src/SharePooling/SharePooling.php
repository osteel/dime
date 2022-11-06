<?php

namespace Domain\SharePooling;

use Domain\Enums\FiatCurrency;
use Domain\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\SharePooling\Exceptions\SharePoolingException;
use Domain\SharePooling\Services\SharePoolingTokenDisposalProcessor;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\SharePooling\Services\SharePoolingTokenAcquisitionProcessor;
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

        $disposalsToRevert = SharePoolingTokenAcquisitionProcessor::getSharePoolingTokenDisposalsToRevert(
            transactions: $this->transactions,
            date: $action->date,
            quantity: $action->quantity,
        );

        // Revert the disposals first
        foreach ($disposalsToRevert as $disposal) {
            $this->recordThat(new SharePoolingTokenDisposalReverted(
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

    public function applySharePoolingTokenDisposalReverted(SharePoolingTokenDisposalReverted $event): void
    {
        // Replace the disposal in the array with the same disposal, but with reset quantities. This
        // way, when several disposals are being replayed, a disposal won't be matched with future
        // acquisitions within the next 30 days if these acquisitions have disposals on the same day
        $this->transactions->add($event->sharePoolingTokenDisposal->copyAsReverted());

        // @TODO also restore the quantities previously deducted from the acquisitions that the disposal was initially
        // matched with. Should probably use a method from SharePoolingTokenDisposalProcessor. Is it possible to do
        // it without tracking which disposals an acquisition's same-day and 30-day quantities were matched with?
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
        $availableQuantity = $this->transactions->madeBeforeOrOn($action->date)
            ->excludeReverted()
            ->quantity();

        if ($action->quantity->isGreaterThan($availableQuantity)) {
            throw SharePoolingException::insufficientQuantityAvailable(
                sharePoolingId: $action->sharePoolingId,
                disposalQuantity: $action->quantity,
                availableQuantity: $availableQuantity,
            );
        }

        // @TODO should also revert the disposals that would have been matched with acquisitions
        // made on the same day as the current disposal, that were within 30 days of those disposals

        // @TODO get those disposals here

        // @TODO if there aren't any, process the disposal normally and record the event
        // if ($disposalsToRevert->isEmpty()) {
            $sharePoolingTokenDisposal = SharePoolingTokenDisposalProcessor::process(
                transactions: $this->transactions->copy(),
                date: $action->date,
                quantity: $action->quantity,
                disposalProceeds: $action->disposalProceeds,
                position: $action->position,
            );

            $this->recordThat(new SharePoolingTokenDisposedOf(
                sharePoolingId: $action->sharePoolingId,
                sharePoolingTokenDisposal: $sharePoolingTokenDisposal,
            ));

            // return;
        //}

        // @TODO If there are some, revert them here
        /*foreach ($disposalsToRevert as $disposal) {
            $this->recordThat(new SharePoolingTokenDisposalReverted(
                sharePoolingId: $action->sharePoolingId,
                sharePoolingTokenDisposal: $disposal,
            ));
        }*/

        // @TODO add the current disposal to the transactions, as reverted, so previous disposals
        // don't try to match their 30-day quantity with the disposal's same-day acquisitions
        /*$this->transactions->add($disposal = new SharePoolingTokenDisposal(
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $action->disposalProceeds->nilAmount(),
            disposalProceeds: $action->disposalProceeds,
            sameDayQuantity: Quantity::zero(),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: $action->quantity,
            reverted: true,
        ));*/

        // @TODO And replay them here, adding the current disposal to the end
        /*$disposalsToRevert->add($disposal); // @TODO make sure it's got assigned a position by the `add` method above
        foreach ($disposalsToRevert as $disposal) {
            $this->disposeOf(new DisposeOfSharePoolingToken(
                sharePoolingId: $action->sharePoolingId,
                date: $disposal->date,
                quantity: $disposal->quantity,
                disposalProceeds: $disposal->disposalProceeds,
                position: $disposal->getPosition(),
            ));
        }*/
    }

    public function applySharePoolingTokenDisposedOf(SharePoolingTokenDisposedOf $event): void
    {
        $this->transactions->add($event->sharePoolingTokenDisposal);
    }
}
