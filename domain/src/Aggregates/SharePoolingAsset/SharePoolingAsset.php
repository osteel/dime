<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\Contracts\Timely;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\RevertSharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\SharePoolingAsset\Exceptions\SharePoolingAssetException;
use Domain\Aggregates\SharePoolingAsset\Services\DisposalProcessor\DisposalProcessor;
use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\QuantityAdjuster;
use Domain\Aggregates\SharePoolingAsset\Services\ReversionFinder\ReversionFinder;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposals;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactions;
use Domain\Enums\FiatCurrency;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use Stringable;

/**
 * @implements AggregateRoot<SharePoolingAssetId>
 * @property SharePoolingAssetId $aggregateRootId
 */
class SharePoolingAsset implements AggregateRoot
{
    /** @phpstan-use AggregateRootBehaviour<SharePoolingAssetId> */
    use AggregateRootBehaviour;

    private ?FiatCurrency $fiatCurrency = null;
    private ?LocalDate $previousTransactionDate = null;
    private readonly SharePoolingAssetTransactions $transactions;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = SharePoolingAssetId::fromString($aggregateRootId->toString());
        $this->transactions = SharePoolingAssetTransactions::make();
    }

    /** @throws SharePoolingAssetException */
    public function acquire(AcquireSharePoolingAsset $action): void
    {
        $this->checkCurrency($action->costBasis->currency, $action);
        $this->checkChronology($action);

        $disposalsToRevert = ReversionFinder::disposalsToRevertOnAcquisition(
            acquisition: $action,
            transactions: $this->transactions,
        );

        $this->revertDisposals($disposalsToRevert);

        // Record the new acquisition
        $this->recordThat(new SharePoolingAssetAcquired(
            sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
                date: $action->date,
                quantity: $action->quantity,
                costBasis: $action->costBasis,
            ),
        ));

        $this->replayDisposals($disposalsToRevert);
    }

    public function applySharePoolingAssetAcquired(SharePoolingAssetAcquired $event): void
    {
        $this->fiatCurrency ??= $event->sharePoolingAssetAcquisition->costBasis->currency;
        $this->previousTransactionDate = $event->sharePoolingAssetAcquisition->date;
        $this->transactions->add($event->sharePoolingAssetAcquisition);
    }

    private function revertDisposal(RevertSharePoolingAssetDisposal $action): void
    {
        // Restore quantities deducted from the acquisitions that the disposal was initially matched with
        QuantityAdjuster::revertDisposal($action->sharePoolingAssetDisposal, $this->transactions);

        $this->recordThat(new SharePoolingAssetDisposalReverted(
            sharePoolingAssetDisposal: $action->sharePoolingAssetDisposal,
        ));
    }

    public function applySharePoolingAssetDisposalReverted(SharePoolingAssetDisposalReverted $event): void
    {
        // Replace the disposal in the array with the same disposal, but with reset quantities. This
        // way, when several disposals are being replayed, a disposal won't be matched with future
        // acquisitions within the next 30 days if these acquisitions have disposals on the same day
        $this->transactions->add($event->sharePoolingAssetDisposal->copyAsUnprocessed());
    }

    /** @throws SharePoolingAssetException */
    public function disposeOf(DisposeOfSharePoolingAsset $action): void
    {
        $this->checkCurrency($action->proceeds->currency, $action);

        if (! $action->isReplay()) {
            $this->checkChronology($action);
        }

        // We check the absolute available quantity up to and including the disposal's
        // date, excluding potential reverted disposals made later on that day
        $previousTransactions = $this->transactions->madeBeforeOrOn($action->date);
        assert($previousTransactions instanceof SharePoolingAssetTransactions);
        $availableQuantity = $previousTransactions->processed()->quantity();

        if ($action->quantity->isGreaterThan($availableQuantity)) {
            throw SharePoolingAssetException::insufficientQuantity(
                sharePoolingAssetId: $this->aggregateRootId,
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
        $this->transactions->add($disposal = (new SharePoolingAssetDisposal(
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $action->proceeds->zero(),
            proceeds: $action->proceeds,
            processed: false,
        ))->setPosition($action->position));

        $this->replayDisposals($disposalsToRevert);

        $this->recordDisposal($action, $disposal->getPosition());
    }

    public function applySharePoolingAssetDisposedOf(SharePoolingAssetDisposedOf $event): void
    {
        $this->previousTransactionDate = $event->sharePoolingAssetDisposal->date;
        $this->transactions->add($event->sharePoolingAssetDisposal);
    }

    private function recordDisposal(DisposeOfSharePoolingAsset $action, ?int $position): void
    {
        $sharePoolingAssetDisposal = DisposalProcessor::process(
            disposal: $action,
            transactions: $this->transactions,
            position: $position,
        );

        $this->recordThat(new SharePoolingAssetDisposedOf(sharePoolingAssetDisposal: $sharePoolingAssetDisposal));
    }

    private function revertDisposals(SharePoolingAssetDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            $this->revertDisposal(new RevertSharePoolingAssetDisposal(sharePoolingAssetDisposal: $disposal));
        }
    }

    private function replayDisposals(SharePoolingAssetDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            $this->disposeOf(new DisposeOfSharePoolingAsset(
                date: $disposal->date,
                quantity: $disposal->quantity,
                proceeds: $disposal->proceeds,
                position: $disposal->getPosition(),
            ));
        }
    }

    /** @throws SharePoolingAssetException */
    private function checkCurrency(FiatCurrency $incoming, Stringable $action): void
    {
        if (is_null($this->fiatCurrency) || $this->fiatCurrency === $incoming) {
            return;
        }

        throw SharePoolingAssetException::currencyMismatch(
            sharePoolingAssetId: $this->aggregateRootId,
            action: $action,
            from: $this->fiatCurrency,
            to: $incoming,
        );
    }

    /** @throws SharePoolingAssetException */
    private function checkChronology(Timely & Stringable $action): void
    {
        if (is_null($this->previousTransactionDate) || $action->getDate()->isAfterOrEqualTo($this->previousTransactionDate)) {
            return;
        }

        throw SharePoolingAssetException::olderThanPreviousTransaction(
            sharePoolingAssetId: $this->aggregateRootId,
            action: $action,
            previousTransactionDate: $this->previousTransactionDate,
        );
    }
}
