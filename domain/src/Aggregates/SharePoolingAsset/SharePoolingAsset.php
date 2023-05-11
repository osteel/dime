<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\Contracts\Timely;
use Domain\Aggregates\SharePoolingAsset\Actions\Contracts\WithAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposals;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetFiatCurrencySet;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetSet;
use Domain\Aggregates\SharePoolingAsset\Exceptions\SharePoolingAssetException;
use Domain\Aggregates\SharePoolingAsset\Services\DisposalProcessor\DisposalProcessor;
use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\QuantityAdjuster;
use Domain\Aggregates\SharePoolingAsset\Services\ReversionFinder\ReversionFinder;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use Stringable;

/**
 * @implements AggregateRoot<SharePoolingAssetId>
 *
 * @property SharePoolingAssetId $aggregateRootId
 */
class SharePoolingAsset implements AggregateRoot
{
    /** @phpstan-use AggregateRootBehaviour<SharePoolingAssetId> */
    use AggregateRootBehaviour;

    private ?Asset $asset = null;

    private ?FiatCurrency $fiatCurrency = null;

    private ?LocalDate $previousTransactionDate = null;

    private readonly SharePoolingAssetTransactions $transactions;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = SharePoolingAssetId::fromString($aggregateRootId->toString());
        $this->transactions = SharePoolingAssetTransactions::make();
    }

    private function setAsset(Asset $asset): void
    {
        if (is_null($this->asset)) {
            $this->recordThat(new SharePoolingAssetSet($asset));
        }
    }

    public function applySharePoolingAssetSet(SharePoolingAssetSet $event): void
    {
        $this->asset = $event->asset;
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
        $this->setAsset($action->asset);
        $this->setFiatCurrency($action->costBasis->currency);

        $this->validateAsset($action->asset, $action);
        $this->validateCurrency($action->costBasis->currency, $action);
        $this->validateTimeline($action);

        $disposalsToRevert = ReversionFinder::disposalsToRevertOnAcquisition(
            acquisition: $action,
            transactions: $this->transactions,
        );

        $this->revertDisposals($disposalsToRevert);

        // Record the new acquisition
        $this->recordThat(new SharePoolingAssetAcquired(
            acquisition: new SharePoolingAssetAcquisition(
                id: $action->transactionId, // Only ever present for testing purposes
                asset: $action->asset,
                date: $action->date,
                quantity: $action->quantity,
                costBasis: $action->costBasis,
            ),
        ));

        $this->replayDisposals($disposalsToRevert);
    }

    public function applySharePoolingAssetAcquired(SharePoolingAssetAcquired $event): void
    {
        $this->previousTransactionDate = $event->acquisition->date;
        $this->transactions->add($event->acquisition);
    }

    /** @throws SharePoolingAssetException */
    public function disposeOf(DisposeOfSharePoolingAsset $action): void
    {
        $this->validateAsset($action->asset, $action);
        $this->validateCurrency($action->proceeds->currency, $action);

        if (! $action->isReplay()) {
            $this->validateTimeline($action);
        }

        $this->validateDisposalQuantity($action);

        $disposalsToRevert = ReversionFinder::disposalsToRevertOnDisposal(
            disposal: $action,
            transactions: $this->transactions,
        );

        // If there are no disposals to revert, process the current disposal normally
        if ($disposalsToRevert->isEmpty()) {
            $this->recordDisposal($action);

            return;
        }

        $this->revertDisposals($disposalsToRevert);

        // Add the current disposal to the transactions (as unprocessed) so previous disposals
        // don't try to match their 30-day quantity with the disposal's same-day acquisitions
        $this->transactions->add(new SharePoolingAssetDisposal(
            id: $action->transactionId,
            asset: $action->asset,
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $action->proceeds->zero(),
            proceeds: $action->proceeds,
            processed: false,
        ));

        $this->replayDisposals($disposalsToRevert);

        $this->recordDisposal($action);
    }

    private function replayDisposals(SharePoolingAssetDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            assert(! is_null($this->asset));

            $this->disposeOf(new DisposeOfSharePoolingAsset(
                asset: $this->asset,
                transactionId: $disposal->id,
                date: $disposal->date,
                quantity: $disposal->quantity,
                proceeds: $disposal->proceeds,
            ));
        }
    }

    private function recordDisposal(DisposeOfSharePoolingAsset $action): void
    {
        $sharePoolingAssetDisposal = DisposalProcessor::process(
            disposal: $action,
            transactions: $this->transactions,
        );

        $this->recordThat(new SharePoolingAssetDisposedOf(disposal: $sharePoolingAssetDisposal));
    }

    public function applySharePoolingAssetDisposedOf(SharePoolingAssetDisposedOf $event): void
    {
        $this->previousTransactionDate = $event->disposal->date;
        $this->transactions->add($event->disposal);
    }

    private function revertDisposals(SharePoolingAssetDisposals $disposals): void
    {
        foreach ($disposals as $disposal) {
            // Restore quantities deducted from the acquisitions whose quantities were allocated to the disposal
            QuantityAdjuster::revertDisposal($disposal, $this->transactions);

            $this->recordThat(new SharePoolingAssetDisposalReverted(disposal: $disposal));
        }
    }

    public function applySharePoolingAssetDisposalReverted(SharePoolingAssetDisposalReverted $event): void
    {
        // Replace the disposal in the array with the same disposal, but with reset quantities. This
        // way, when several disposals are being replayed, a disposal won't be matched with future
        // acquisitions within the next 30 days if these acquisitions have disposals on the same day
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
    private function validateAsset(Asset $incoming, Stringable&WithAsset $action): void
    {
        if (is_null($this->asset) || $incoming->is($this->asset)) {
            return;
        }

        throw SharePoolingAssetException::assetMismatch(action: $action, incoming: $incoming);
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
