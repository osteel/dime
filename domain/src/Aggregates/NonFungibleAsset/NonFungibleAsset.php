<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\Contracts\Timely;
use Domain\Aggregates\NonFungibleAsset\Actions\Contracts\WithAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetAcquired;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetCostBasisIncreased;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetDisposedOf;
use Domain\Aggregates\NonFungibleAsset\Exceptions\NonFungibleAssetException;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use Stringable;

/** @property NonFungibleAssetId $aggregateRootId */
final class NonFungibleAsset implements NonFungibleAssetContract
{
    /** @phpstan-use AggregateRootBehaviour<NonFungibleAssetId> */
    use AggregateRootBehaviour;

    private bool $acquired = false;

    private ?FiatAmount $costBasis = null;

    private ?LocalDate $previousTransactionDate = null;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = NonFungibleAssetId::fromString($aggregateRootId->toString());
    }

    public function isAlreadyAcquired(): bool
    {
        return $this->acquired;
    }

    /** @throws NonFungibleAssetException */
    public function acquire(AcquireNonFungibleAsset $action): void
    {
        $this->isAlreadyAcquired() === false || throw NonFungibleAssetException::alreadyAcquired($action->asset);

        $action->asset->isNonFungible || throw NonFungibleAssetException::assetIsFungible($action);

        $this->recordThat(new NonFungibleAssetAcquired(
            date: $action->date,
            costBasis: $action->costBasis,
            forFiat: $action->forFiat,
        ));
    }

    public function applyNonFungibleAssetAcquired(NonFungibleAssetAcquired $event): void
    {
        $this->acquired = true;
        $this->costBasis = $event->costBasis;
        $this->previousTransactionDate = $event->date;
    }

    /** @throws NonFungibleAssetException */
    public function increaseCostBasis(IncreaseNonFungibleAssetCostBasis $action): void
    {
        $this->isAlreadyAcquired()
            || throw NonFungibleAssetException::cannotIncreaseCostBasisBeforeAcquisition($action->asset);

        $this->validateCurrency($action, $action->costBasisIncrease->currency);
        $this->validateTimeline($action);

        $this->recordThat(new NonFungibleAssetCostBasisIncreased(
            date: $action->date,
            costBasisIncrease: $action->costBasisIncrease,
            newCostBasis: $this->costBasis?->plus($action->costBasisIncrease) ?? $action->costBasisIncrease,
            forFiat: $action->forFiat,
        ));
    }

    public function applyNonFungibleAssetCostBasisIncreased(NonFungibleAssetCostBasisIncreased $event): void
    {
        assert(! is_null($this->costBasis));

        $this->costBasis = $event->newCostBasis;
        $this->previousTransactionDate = $event->date;
    }

    /** @throws NonFungibleAssetException */
    public function disposeOf(DisposeOfNonFungibleAsset $action): void
    {
        $this->isAlreadyAcquired() || throw NonFungibleAssetException::cannotDisposeOfBeforeAcquisition($action->asset);

        $this->validateCurrency($action, $action->proceeds->currency);
        $this->validateTimeline($action);

        assert(! is_null($this->costBasis));

        $this->recordThat(new NonFungibleAssetDisposedOf(
            date: $action->date,
            costBasis: $this->costBasis,
            proceeds: $action->proceeds,
            forFiat: $action->forFiat,
        ));
    }

    public function applyNonFungibleAssetDisposedOf(NonFungibleAssetDisposedOf $event): void
    {
        $this->acquired = false;
        $this->costBasis = null;
        $this->previousTransactionDate = null;
    }

    /** @throws NonFungibleAssetException */
    private function validateCurrency(Stringable&WithAsset $action, FiatCurrency $incoming): void
    {
        if (is_null($this->costBasis) || $this->costBasis->currency === $incoming) {
            return;
        }

        throw NonFungibleAssetException::currencyMismatch(
            action: $action,
            current: $this->costBasis->currency,
            incoming: $incoming,
        );
    }

    /** @throws NonFungibleAssetException */
    private function validateTimeline(Stringable&Timely&WithAsset $action): void
    {
        if (is_null($this->previousTransactionDate) || $action->getDate()->isAfterOrEqualTo($this->previousTransactionDate)) {
            return;
        }

        throw NonFungibleAssetException::olderThanPreviousTransaction(
            action: $action,
            previousTransactionDate: $this->previousTransactionDate,
        );
    }
}
