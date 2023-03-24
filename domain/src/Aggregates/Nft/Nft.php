<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\Nft\Actions\AcquireNft;
use Domain\Aggregates\Nft\Actions\Contracts\Timely;
use Domain\Aggregates\Nft\Actions\DisposeOfNft;
use Domain\Aggregates\Nft\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Nft\Events\NftAcquired;
use Domain\Aggregates\Nft\Events\NftCostBasisIncreased;
use Domain\Aggregates\Nft\Events\NftDisposedOf;
use Domain\Aggregates\Nft\Exceptions\NftException;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;

/**
 * @implements AggregateRoot<NftId>
 * @property NftId $aggregateRootId
 */
class Nft implements AggregateRoot
{
    /** @phpstan-use AggregateRootBehaviour<NftId> */
    use AggregateRootBehaviour;

    private ?FiatAmount $costBasis = null;
    private ?LocalDate $previousTransactionDate = null;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = NftId::fromString($aggregateRootId->toString());
    }

    public function isAlreadyAcquired(): bool
    {
        return ! is_null($this->costBasis);
    }

    private function isOlderThanPreviousTransaction(Timely $action): bool
    {
        return (bool) $this->previousTransactionDate?->isAfter($action->getDate());
    }

    /** @throws NftException */
    public function acquire(AcquireNft $action): void
    {
        is_null($this->costBasis) || throw NftException::alreadyAcquired($this->aggregateRootId);

        $this->recordThat(new NftAcquired(
            date: $action->date,
            costBasis: $action->costBasis,
        ));
    }

    public function applyNftAcquired(NftAcquired $event): void
    {
        $this->costBasis = $event->costBasis;
        $this->previousTransactionDate = $event->date;
    }

    /** @throws NftException */
    public function increaseCostBasis(IncreaseNftCostBasis $action): void
    {
        ! is_null($this->costBasis) || throw NftException::cannotIncreaseCostBasisBeforeAcquisition($this->aggregateRootId);

        assert(! is_null($this->previousTransactionDate));

        throw_if(
            $this->isOlderThanPreviousTransaction($action),
            NftException::olderThanPreviousTransaction($this->aggregateRootId, $action, $this->previousTransactionDate),
        );

        if ($this->costBasis->currency !== $action->costBasisIncrease->currency) {
            throw NftException::cannotIncreaseCostBasisFromDifferentCurrency(
                nftId: $this->aggregateRootId,
                from: $this->costBasis->currency,
                to: $action->costBasisIncrease->currency,
            );
        }

        $this->recordThat(new NftCostBasisIncreased(
            date: $action->date,
            costBasisIncrease: $action->costBasisIncrease,
        ));
    }

    public function applyNftCostBasisIncreased(NftCostBasisIncreased $event): void
    {
        assert(! is_null($this->costBasis));

        $this->costBasis = $this->costBasis->plus($event->costBasisIncrease);
        $this->previousTransactionDate = $event->date;
    }

    /** @throws NftException */
    public function disposeOf(DisposeOfNft $action): void
    {
        ! is_null($this->costBasis) || throw NftException::cannotDisposeOfBeforeAcquisition($this->aggregateRootId);

        assert(! is_null($this->previousTransactionDate));

        throw_if(
            $this->isOlderThanPreviousTransaction($action),
            NftException::olderThanPreviousTransaction($this->aggregateRootId, $action, $this->previousTransactionDate),
        );

        $this->recordThat(new NftDisposedOf(
            date: $action->date,
            costBasis: $this->costBasis,
            proceeds: $action->proceeds
        ));
    }

    public function applyNftDisposedOf(NftDisposedOf $event): void
    {
        $this->costBasis = null;
        $this->previousTransactionDate = null;
    }
}
