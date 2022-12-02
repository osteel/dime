<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling;

use Domain\Enums\FiatCurrency;
use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\RevertSharePoolingTokenDisposal;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\Aggregates\SharePooling\Exceptions\SharePoolingException;
use Domain\Aggregates\SharePooling\Services\DisposalProcessor\DisposalProcessor;
use Domain\Aggregates\SharePooling\Services\QuantityAdjuster\QuantityAdjuster;
use Domain\Aggregates\SharePooling\Services\ReversionFinder\ReversionFinder;
use Domain\Aggregates\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTransactions;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;

/** @property SharePoolingId $aggregateRootId */
final class SharePooling implements AggregateRoot
{
    use AggregateRootBehaviour;

    private ?FiatCurrency $fiatCurrency = null;
    private SharePoolingTransactions $transactions;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = SharePoolingId::fromString($aggregateRootId->toString());
        $this->transactions = SharePoolingTransactions::make();
    }

    /** @throws SharePoolingException */
    public function acquire(AcquireSharePoolingToken $action): void
    {
        if ($this->fiatCurrency && $this->fiatCurrency !== $action->costBasis->currency) {
            throw SharePoolingException::cannotAcquireFromDifferentCurrency(
                sharePoolingId: $this->aggregateRootId,
                from: $this->fiatCurrency,
                to: $action->costBasis->currency,
            );
        }

        $disposalsToRevert = ReversionFinder::disposalsToRevertOnAcquisition(
            acquisition: $action,
            transactions: $this->transactions,
        );

        $this->revertDisposals($disposalsToRevert);

        // Record the new acquisition
        $this->recordThat(new SharePoolingTokenAcquired(
            sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
                date: $action->date,
                quantity: $action->quantity,
                costBasis: $action->costBasis,
            ),
        ));

        $this->replayDisposals($disposalsToRevert);
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
            throw SharePoolingException::cannotDisposeOfFromDifferentCurrency(
                sharePoolingId: $this->aggregateRootId,
                from: $this->fiatCurrency,
                to: $action->proceeds->currency,
            );
        }

        // We check the absolute available quantity up to and including the disposal's
        // date, excluding potential reverted disposals made later on that day
        $previousTransactions = $this->transactions->madeBeforeOrOn($action->date);
        assert($previousTransactions instanceof SharePoolingTransactions);
        $availableQuantity = $previousTransactions->processed()->quantity();

        if ($action->quantity->isGreaterThan($availableQuantity)) {
            throw SharePoolingException::insufficientQuantity(
                sharePoolingId: $this->aggregateRootId,
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

        $this->revertDisposals($disposalsToRevert);

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

        $this->replayDisposals($disposalsToRevert);

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

        $this->recordThat(new SharePoolingTokenDisposedOf(sharePoolingTokenDisposal: $sharePoolingTokenDisposal));
    }

    private function revertDisposals(SharePoolingTokenDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            $this->revertDisposal(new RevertSharePoolingTokenDisposal(sharePoolingTokenDisposal: $disposal));
        }
    }

    private function replayDisposals(SharePoolingTokenDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            $this->disposeOf(new DisposeOfSharePoolingToken(
                date: $disposal->date,
                quantity: $disposal->quantity,
                proceeds: $disposal->proceeds,
                position: $disposal->getPosition(),
            ));
        }
    }
}
