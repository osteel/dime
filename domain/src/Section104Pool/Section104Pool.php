<?php

namespace Domain\Section104Pool;

use Domain\Section104Pool\Actions\AcquireSection104PoolTokens;
use Domain\Section104Pool\Events\Section104PoolTokensAcquired;
use Domain\Services\Math;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

final class Section104Pool implements AggregateRoot
{
    use AggregateRootBehaviour;

    public string $quantity = '0';
    public ?FiatAmount $costBasis = null;
    public ?FiatAmount $averageCostBasisPerUnit = null;

    public function acquire(AcquireSection104PoolTokens $action): void
    {
        $previousCostBasis = $this->costBasis ?? new FiatAmount('0', $action->costBasis->currency);
        $previousAverageCostBasisPerUnit = $this->averageCostBasisPerUnit ??  new FiatAmount('0', $action->costBasis->currency);

        $newCostBasis = new FiatAmount(
            amount: Math::add($previousCostBasis->amount, $action->costBasis->amount),
            currency: $previousCostBasis->currency,
        );

        $newQuantity = Math::add($this->quantity, $action->quantity);

        $newAverageCostBasisPerUnit = new FiatAmount(
            amount: Math::div($newCostBasis->amount, $newQuantity),
            currency: $previousAverageCostBasisPerUnit->currency,
        );

        $this->recordThat(new Section104PoolTokensAcquired(
            section104PoolId: $this->aggregateRootId,
            previousQuantity: $this->quantity,
            extraQuantity: $action->quantity,
            newQuantity: $newQuantity,
            previousCostBasis: $previousCostBasis,
            extraCostBasis: $action->costBasis,
            newCostBasis: $newCostBasis,
            previousAverageCostBasisPerUnit: $previousAverageCostBasisPerUnit,
            newAverageCostBasisPerUnit: $newAverageCostBasisPerUnit,
        ));
    }

    public function applySection104PoolTokensAcquired(Section104PoolTokensAcquired $event): void
    {
        $this->quantity = $event->newQuantity;
        $this->costBasis = $event->newCostBasis;
        $this->averageCostBasisPerUnit = $event->newAverageCostBasisPerUnit;
    }
}
