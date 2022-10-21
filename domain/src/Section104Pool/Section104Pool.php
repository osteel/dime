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
            throw Section104PoolException::cannotAcquireFromDifferentFiatCurrency(
                section104PoolId: $action->section104PoolId,
                from: $this->costBasis->currency,
                to: $action->costBasis->currency,
            );
        }

        $this->recordThat(new Section104PoolTokenAcquired(
            section104PoolId: $action->section104PoolId,
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $action->costBasis,
        ));
    }

    public function applySection104PoolTokenAcquired(Section104PoolTokenAcquired $event): void
    {
        $newQuantity = Math::add($this->quantity, $event->quantity);

        $previousCostBasis = $this->costBasis ?? new FiatAmount('0', $event->costBasis->currency);
        $previousAverageCostBasisPerUnit = $this->averageCostBasisPerUnit ??  new FiatAmount('0', $event->costBasis->currency);

        $newCostBasis = new FiatAmount(
            amount: Math::add($previousCostBasis->amount, $event->costBasis->amount),
            currency: $previousCostBasis->currency,
        );

        $newAverageCostBasisPerUnit = new FiatAmount(
            amount: Math::div($newCostBasis->amount, $newQuantity),
            currency: $previousAverageCostBasisPerUnit->currency,
        );

        $this->quantity = $newQuantity;
        $this->costBasis = $newCostBasis;
        $this->averageCostBasisPerUnit = $newAverageCostBasisPerUnit;
    }

    public function disposeOf(DisposeOfSection104PoolToken $action): void
    {
        if ($this->costBasis && $this->costBasis->currency !== $action->disposalProceeds->currency) {
            throw Section104PoolException::cannotDisposeOfFromDifferentFiatCurrency(
                section104PoolId: $action->section104PoolId,
                from: $this->costBasis->currency,
                to: $action->disposalProceeds->currency,
            );
        }

        if (Math::gt($action->quantity, $this->quantity)) {
            throw Section104PoolException::disposalQuantityIsTooHigh(
                section104PoolId: $action->section104PoolId,
                disposalQuantity: $action->quantity,
                availableQuantity: $this->quantity,
            );
        }

        $costBasis = new FiatAmount(
            amount: Math::mul($this->averageCostBasisPerUnit->amount, $action->quantity),
            currency: $this->averageCostBasisPerUnit->currency,
        );

        $this->recordThat(new Section104PoolTokenDisposedOf(
            section104PoolId: $action->section104PoolId,
            date: $action->date,
            quantity: $action->quantity,
            disposalProceeds: $action->disposalProceeds,
            costBasis: $costBasis,
        ));
    }

    public function applySection104PoolTokenDisposedOf(Section104PoolTokenDisposedOf $event): void
    {
        assert(! is_null($this->costBasis));
        assert(! is_null($this->averageCostBasisPerUnit));

        $newQuantity = Math::sub($this->quantity, $event->quantity);

        $newCostBasis = new FiatAmount(
            amount: Math::sub(
                $this->costBasis->amount,
                Math::mul($event->quantity, $this->averageCostBasisPerUnit->amount),
            ),
            currency: $this->costBasis->currency,
        );

        $this->quantity = $newQuantity;
        $this->costBasis = $newCostBasis;
    }
}
