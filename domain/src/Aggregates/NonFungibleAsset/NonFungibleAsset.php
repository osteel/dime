<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\Contracts\Timely;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetAcquired;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetCostBasisIncreased;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetDisposedOf;
use Domain\Aggregates\NonFungibleAsset\Exceptions\NonFungibleAssetException;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;

/**
 * @implements AggregateRoot<NonFungibleAssetId>
 * @property NonFungibleAssetId $aggregateRootId
 */
class NonFungibleAsset implements AggregateRoot
{
    /** @phpstan-use AggregateRootBehaviour<NonFungibleAssetId> */
    use AggregateRootBehaviour;

    private ?FiatAmount $costBasis = null;
    private ?LocalDate $previousTransactionDate = null;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = NonFungibleAssetId::fromString($aggregateRootId->toString());
    }

    public function isAlreadyAcquired(): bool
    {
        return ! is_null($this->costBasis);
    }

    private function isOlderThanPreviousTransaction(Timely $action): bool
    {
        return (bool) $this->previousTransactionDate?->isAfter($action->getDate());
    }

    /** @throws NonFungibleAssetException */
    public function acquire(AcquireNonFungibleAsset $action): void
    {
        is_null($this->costBasis) || throw NonFungibleAssetException::alreadyAcquired($this->aggregateRootId);

        $this->recordThat(new NonFungibleAssetAcquired(
            date: $action->date,
            costBasis: $action->costBasis,
        ));
    }

    public function applyNonFungibleAssetAcquired(NonFungibleAssetAcquired $event): void
    {
        $this->costBasis = $event->costBasis;
        $this->previousTransactionDate = $event->date;
    }

    /** @throws NonFungibleAssetException */
    public function increaseCostBasis(IncreaseNonFungibleAssetCostBasis $action): void
    {
        ! is_null($this->costBasis) || throw NonFungibleAssetException::cannotIncreaseCostBasisBeforeAcquisition($this->aggregateRootId);

        if ($this->previousTransactionDate && $this->isOlderThanPreviousTransaction($action)) {
            throw NonFungibleAssetException::olderThanPreviousTransaction($this->aggregateRootId, $action, $this->previousTransactionDate);
        }

        if ($this->costBasis->currency !== $action->costBasisIncrease->currency) {
            throw NonFungibleAssetException::cannotIncreaseCostBasisFromDifferentCurrency(
                nonFungibleAssetId: $this->aggregateRootId,
                current: $this->costBasis->currency,
                incoming: $action->costBasisIncrease->currency,
            );
        }

        $this->recordThat(new NonFungibleAssetCostBasisIncreased(
            date: $action->date,
            costBasisIncrease: $action->costBasisIncrease,
        ));
    }

    public function applyNonFungibleAssetCostBasisIncreased(NonFungibleAssetCostBasisIncreased $event): void
    {
        assert(! is_null($this->costBasis));

        $this->costBasis = $this->costBasis->plus($event->costBasisIncrease);
        $this->previousTransactionDate = $event->date;
    }

    /** @throws NonFungibleAssetException */
    public function disposeOf(DisposeOfNonFungibleAsset $action): void
    {
        ! is_null($this->costBasis) || throw NonFungibleAssetException::cannotDisposeOfBeforeAcquisition($this->aggregateRootId);

        if ($this->previousTransactionDate && $this->isOlderThanPreviousTransaction($action)) {
            throw NonFungibleAssetException::olderThanPreviousTransaction($this->aggregateRootId, $action, $this->previousTransactionDate);
        }

        $this->recordThat(new NonFungibleAssetDisposedOf(
            date: $action->date,
            costBasis: $this->costBasis,
            proceeds: $action->proceeds
        ));
    }

    public function applyNonFungibleAssetDisposedOf(NonFungibleAssetDisposedOf $event): void
    {
        $this->costBasis = null;
        $this->previousTransactionDate = null;
    }
}
