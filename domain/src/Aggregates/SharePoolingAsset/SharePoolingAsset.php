<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\Contracts\Timely;
use Domain\Aggregates\SharePoolingAsset\Actions\Contracts\WithAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposals;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetFiatCurrencySet;
use Domain\Aggregates\SharePoolingAsset\Exceptions\SharePoolingAssetException;
use Domain\Aggregates\SharePoolingAsset\Services\DisposalBuilder\DisposalBuilder;
use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\QuantityAdjuster;
use Domain\Aggregates\SharePoolingAsset\Services\ReversionFinder\ReversionFinder;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use Domain\Enums\FiatCurrency;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use Stringable;

/** @property SharePoolingAssetId $aggregateRootId */
final class SharePoolingAsset implements SharePoolingAssetContract
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

    private function setFiatCurrency(FiatCurrency $fiatCurrency): void
    {
        if (is_null($this->fiatCurrency)) {
            $this->recordThat(new SharePoolingAssetFiatCurrencySet($fiatCurrency));
        }
    }

    public function applySharePoolingAssetFiatCurrencySet(SharePoolingAssetFiatCurrencySet $event): void
    {
        $this->fiatCurrency = $event->fiatCurrency;
    }

    /** @throws SharePoolingAssetException */
    public function acquire(AcquireSharePoolingAsset $action): void
    {
        $this->setFiatCurrency($action->costBasis->currency);

        $this->validateCurrency($action->costBasis->currency, $action);
        $this->validateTimeline($action);

        $disposalsToRevert = ReversionFinder::disposalsToRevertOnAcquisition(
            acquisition: $action,
            transactions: $this->transactions->copy(),
        );

        $disposalsToRevert->isEmpty() || $this->revertDisposals($disposalsToRevert);

        // Record the new acquisition
        $this->recordThat(new SharePoolingAssetAcquired(
            acquisition: new SharePoolingAssetAcquisition(
                id: $action->transactionId, // Only ever present for testing purposes
                date: $action->date,
                quantity: $action->quantity,
                costBasis: $action->costBasis,
                forFiat: $action->forFiat,
            ),
        ));

        $disposalsToRevert->isEmpty() || $this->replayDisposals($disposalsToRevert);
    }

    public function applySharePoolingAssetAcquired(SharePoolingAssetAcquired $event): void
    {
        $this->previousTransactionDate = $event->acquisition->date;
        // The reason for cloning here is for cases where an acquisition causes some disposals to be reverted before
        // the acquisition is recorded and the disposals subsequently replayed. The acquisition should be recorded
        // with its same-day and 30-day quantities to zero, because at the time of the event the disposals haven't
        // been replayed yet. But the aggregate is only persisted after the disposals have been reverted, the
        // acquisition has been processed *and* the disposals have been replayed. Since the latter update the
        // acquisition's same-day and 30-day quantities, these updates occur before the acquisition's event
        // is recorded, meaning the event is stored with the updated quantities. As a result, whenever the
        // aggregate is recreated from its events, the acquisition already has a same-day and/or 30-day
        // quantity, but upon replaying the subsequent disposals, these quantities are updated *again*.
        $this->transactions->add(clone $event->acquisition);
    }

    /** @throws SharePoolingAssetException */
    public function disposeOf(DisposeOfSharePoolingAsset $action): void
    {
        $this->validateCurrency($action->proceeds->currency, $action);

        $action->isReplay() || $this->validateTimeline($action);

        $this->validateDisposalQuantity($action);

        $disposalsToRevert = ReversionFinder::disposalsToRevertOnDisposal(
            disposal: $action,
            transactions: $this->transactions->copy(),
        );

        $disposalsToRevert->isEmpty() || $this->revertDisposals($disposalsToRevert);

        $sharePoolingAssetDisposal = DisposalBuilder::process(
            disposal: $action,
            transactions: $this->transactions->copy(),
        );

        $this->recordThat(new SharePoolingAssetDisposedOf(disposal: $sharePoolingAssetDisposal));

        $disposalsToRevert->isEmpty() || $this->replayDisposals($disposalsToRevert);
    }

    private function replayDisposals(SharePoolingAssetDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            $this->disposeOf(new DisposeOfSharePoolingAsset(
                asset: $this->aggregateRootId->toAsset(),
                transactionId: $disposal->id,
                date: $disposal->date,
                quantity: $disposal->quantity,
                proceeds: $disposal->proceeds,
                forFiat: $disposal->forFiat,
            ));
        }
    }

    public function applySharePoolingAssetDisposedOf(SharePoolingAssetDisposedOf $event): void
    {
        // Adjust quantities for acquisitions whose quantities were allocated to the disposal
        QuantityAdjuster::applyDisposal($event->disposal, $this->transactions);

        $this->previousTransactionDate = $event->disposal->date;
        $this->transactions->add($event->disposal);
    }

    private function revertDisposals(SharePoolingAssetDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            $this->recordThat(new SharePoolingAssetDisposalReverted(disposal: $disposal));
        }
    }

    public function applySharePoolingAssetDisposalReverted(SharePoolingAssetDisposalReverted $event): void
    {
        // Restore quantities deducted from acquisitions whose quantities were allocated to the disposal
        QuantityAdjuster::revertDisposal($event->disposal, $this->transactions);

        // Replace the disposal in the array with the same disposal, as unprocessed. This way we save the disposal's
        // position in the array but it's ignored for calculations (e.g. the validation of the disposed of quantity)
        $this->transactions->add($event->disposal->copyAsUnprocessed());
    }

    /** @throws SharePoolingAssetException */
    private function validateDisposalQuantity(DisposeOfSharePoolingAsset $action): void
    {
        // We check the absolute available quantity up to and including the disposal's
        // date, excluding potential reverted disposals made later on that day
        $previousTransactions = $this->transactions->madeBeforeOrOn($action->date);
        assert($previousTransactions instanceof SharePoolingAssetTransactions);
        $availableQuantity = $previousTransactions->processed()->quantity();

        if ($action->quantity->isGreaterThan($availableQuantity)) {
            throw SharePoolingAssetException::insufficientQuantity(
                asset: $action->asset,
                disposalQuantity: $action->quantity,
                availableQuantity: $availableQuantity,
            );
        }
    }

    /** @throws SharePoolingAssetException */
    private function validateCurrency(FiatCurrency $incoming, Stringable&WithAsset $action): void
    {
        if (is_null($this->fiatCurrency) || $this->fiatCurrency === $incoming) {
            return;
        }

        throw SharePoolingAssetException::currencyMismatch(
            action: $action,
            current: $this->fiatCurrency,
            incoming: $incoming,
        );
    }

    /** @throws SharePoolingAssetException */
    private function validateTimeline(Stringable&Timely&WithAsset $action): void
    {
        if (is_null($this->previousTransactionDate) || $action->getDate()->isAfterOrEqualTo($this->previousTransactionDate)) {
            return;
        }

        throw SharePoolingAssetException::olderThanPreviousTransaction(
            action: $action,
            previousTransactionDate: $this->previousTransactionDate,
        );
    }
}
