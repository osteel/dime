<?php

namespace Domain\Section104Pool;

use Domain\Section104Pool\Actions\AcquireSection104PoolToken;
use Domain\Section104Pool\Actions\DisposeOfSection104PoolToken;
use Domain\Section104Pool\Events\Section104PoolTokenAcquired;
use Domain\Section104Pool\Events\Section104PoolTokenDisposedOf;
use Domain\Section104Pool\Exceptions\Section104PoolException;
use Domain\Services\Math\Math;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

final class Section104Pool implements AggregateRoot
{
    use AggregateRootBehaviour;

    public string $quantity = '0';
    public ?FiatAmount $costBasis = null;
    public ?FiatAmount $averageCostBasisPerUnit = null;

    public function acquire(AcquireSection104PoolToken $action): void
    {
        if ($this->costBasis && $this->costBasis->currency !== $action->costBasis->currency) {
            throw Section104PoolException::cannotAcquireDifferentCostBasisCurrency(
                section104PoolId: $this->aggregateRootId,
                from: $this->costBasis->currency,
                to: $action->costBasis->currency,
            );
        }

        $newQuantity = Math::add($this->quantity, $action->quantity);

        $previousCostBasis = $this->costBasis ?? new FiatAmount('0', $action->costBasis->currency);
        $previousAverageCostBasisPerUnit = $this->averageCostBasisPerUnit ??  new FiatAmount('0', $action->costBasis->currency);

        $newCostBasis = new FiatAmount(
            amount: Math::add($previousCostBasis->amount, $action->costBasis->amount),
            currency: $previousCostBasis->currency,
        );

        $newAverageCostBasisPerUnit = new FiatAmount(
            amount: Math::div($newCostBasis->amount, $newQuantity),
            currency: $previousAverageCostBasisPerUnit->currency,
        );

        $this->recordThat(new Section104PoolTokenAcquired(
            section104PoolId: $this->aggregateRootId,
            previousQuantity: $this->quantity,
            acquiredQuantity: $action->quantity,
            newQuantity: $newQuantity,
            previousCostBasis: $previousCostBasis,
            acquisitionCostBasis: $action->costBasis,
            newCostBasis: $newCostBasis,
            previousAverageCostBasisPerUnit: $previousAverageCostBasisPerUnit,
            newAverageCostBasisPerUnit: $newAverageCostBasisPerUnit,
        ));
    }

    public function applySection104PoolTokenAcquired(Section104PoolTokenAcquired $event): void
    {
        $this->quantity = $event->newQuantity;
        $this->costBasis = $event->newCostBasis;
        $this->averageCostBasisPerUnit = $event->newAverageCostBasisPerUnit;
    }

    public function disposeOf(DisposeOfSection104PoolToken $action): void
    {
        if (Math::gt($action->quantity, $this->quantity)) {
            throw Section104PoolException::disposalQuantityIsTooHigh(
                section104PoolId: $this->aggregateRootId,
                disposalQuantity: $action->quantity,
                availableQuantity: $this->quantity,
            );
        }

        $newQuantity = Math::sub($this->quantity, $action->quantity);

        $newCostBasis = new FiatAmount(
            amount: Math::sub(
                $this->costBasis->amount,
                Math::mul($action->quantity, $this->averageCostBasisPerUnit->amount),
            ),
            currency: $this->costBasis->currency,
        );

        $this->recordThat(new Section104PoolTokenDisposedOf(
            section104PoolId: $this->aggregateRootId,
            previousQuantity: $this->quantity,
            disposedOfQuantity: $action->quantity,
            newQuantity: $newQuantity,
            previousCostBasis: $this->costBasis,
            averageCostBasisPerUnit: $this->averageCostBasisPerUnit,
            newCostBasis: $newCostBasis,
            disposalProceeds: $action->disposalProceeds,
        ));
    }

    public function applySection104PoolTokenDisposedOf(Section104PoolTokenDisposedOf $event): void
    {
        $this->quantity = $event->newQuantity;
        $this->costBasis = $event->newCostBasis;
    }
}
