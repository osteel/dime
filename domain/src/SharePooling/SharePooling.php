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
use Domain\SharePooling\Services\DisposalProcessor;
use Domain\SharePooling\Services\QuantityAdjuster;
use Domain\SharePooling\Services\ReversionFinder;
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

        $disposalsToRevert = ReversionFinder::disposalsToRevertOnAcquisition(
            acquisition: $action,
            transactions: $this->transactions,
        );

        $this->revertDisposals($action->sharePoolingId, $disposalsToRevert);

        // Record the new acquisition
        $this->recordThat(new SharePoolingTokenAcquired(
            sharePoolingId: $action->sharePoolingId,
            sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
                date: $action->date,
                quantity: $action->quantity,
                costBasis: $action->costBasis,
            ),
        ));

        $this->replayDisposals($action->sharePoolingId, $disposalsToRevert);
    }

    public function applySharePoolingTokenAcquired(SharePoolingTokenAcquired $event): void
    {
        $this->fiatCurrency ??= $event->sharePoolingTokenAcquisition->costBasis->currency;

        $this->transactions->add($event->sharePoolingTokenAcquisition);
    }

    private function revertDisposal(RevertSharePoolingTokenDisposal $action): void
    {
        // Restore quantities deducted from the acquisitions that the disposal was initially matched with
        QuantityAdjuster::revertDisposal($action->sharePoolingTokenDisposal, $this->transactions);

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
        if ($this->fiatCurrency && $this->fiatCurrency !== $action->proceeds->currency) {
            throw SharePoolingException::cannotDisposeOfFromDifferentFiatCurrency(
                sharePoolingId: $action->sharePoolingId,
                from: $this->fiatCurrency,
                to: $action->proceeds->currency,
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

        $disposalsToRevert = ReversionFinder::disposalsToRevertOnDisposal(
            disposal: $action,
            transactions: $this->transactions,
        );

        // If there are no disposals to revert, process the current disposal normally
        if ($disposalsToRevert->isEmpty()) {
            $this->recordDisposal($action, $action->position);

            return;
        }

        $this->revertDisposals($action->sharePoolingId, $disposalsToRevert);

        // Add the current disposal to the transactions (as unprocessed) so previous disposals
        // don't try to match their 30-day quantity with the disposal's same-day acquisitions
        $this->transactions->add($disposal = (new SharePoolingTokenDisposal(
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $action->proceeds->nilAmount(),
            proceeds: $action->proceeds,
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
            processed: false,
        ))->setPosition($action->position));

        $this->replayDisposals($action->sharePoolingId, $disposalsToRevert);

        $this->recordDisposal($action, $disposal->getPosition());
    }

    public function applySharePoolingTokenDisposedOf(SharePoolingTokenDisposedOf $event): void
    {
        $this->transactions->add($event->sharePoolingTokenDisposal);
    }

    private function recordDisposal(DisposeOfSharePoolingToken $action, ?int $position): void
    {
        $sharePoolingTokenDisposal = DisposalProcessor::process(
            disposal: $action,
            transactions: $this->transactions,
            position: $position,
        );

        $this->recordThat(new SharePoolingTokenDisposedOf(
            sharePoolingId: $action->sharePoolingId,
            sharePoolingTokenDisposal: $sharePoolingTokenDisposal,
        ));
    }

    private function revertDisposals(SharePoolingId $sharePoolingId, SharePoolingTokenDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            $this->revertDisposal(new RevertSharePoolingTokenDisposal(
                sharePoolingId: $sharePoolingId,
                sharePoolingTokenDisposal: $disposal,
            ));
        }
    }

    private function replayDisposals(SharePoolingId $sharePoolingId, SharePoolingTokenDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            $this->disposeOf(new DisposeOfSharePoolingToken(
                sharePoolingId: $sharePoolingId,
                date: $disposal->date,
                quantity: $disposal->quantity,
                proceeds: $disposal->proceeds,
                position: $disposal->getPosition(),
            ));
        }
    }
}
