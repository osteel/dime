<?php

namespace Domain\Section104Pool;

use Domain\Section104Pool\Actions\AcquireSection104PoolToken;
use Domain\Section104Pool\Actions\DisposeOfSection104PoolToken;
use Domain\Section104Pool\Events\Section104PoolTokenAcquired;
use Domain\Section104Pool\Events\Section104PoolTokenDisposalReverted;
use Domain\Section104Pool\Events\Section104PoolTokenDisposedOf;
use Domain\Section104Pool\Exceptions\Section104PoolException;
use Domain\Section104Pool\Services\DisposalCostBasisCalculator;
use Domain\Section104Pool\ValueObjects\Section104PoolAcquisition;
use Domain\Section104Pool\ValueObjects\Section104PoolDisposal;
use Domain\Section104Pool\ValueObjects\Section104PoolTransactions;
use Domain\Services\Math\Math;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;

final class Section104Pool implements AggregateRoot
{
    use AggregateRootBehaviour;

    private string $quantity = '0';
    private ?FiatAmount $costBasis = null;
    private ?FiatAmount $averageCostBasisPerUnit = null;
    private Section104PoolTransactions $transactions;

    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = $aggregateRootId;

        $this->transactions = Section104PoolTransactions::make();
    }

    /** @throws Section104PoolException */
    public function acquire(AcquireSection104PoolToken $action): void
    {
        if ($this->costBasis && $this->costBasis->currency !== $action->costBasis->currency) {
            throw Section104PoolException::cannotAcquireFromDifferentFiatCurrency(
                section104PoolId: $action->section104PoolId,
                from: $this->costBasis->currency,
                to: $action->costBasis->currency,
            );
        }

        // Go through disposals in the past 30 days FIFO
        $past30DaysDisposals = $this->transactions->disposalsMadeBetween($action->date->minusDays(30), $action->date);
        $disposalsToReplay = [];

        foreach ($past30DaysDisposals as $disposal) {
            // Revert the ones whose quantities are not covered by the acquisitions
            // made in their next 30 days (including the disposal's date)
            $subsequentAcquisitions = $this->transactions->acquisitionsMadeBetween($disposal->date, $disposal->date->plusDays(30));
            if (Math::lt($disposal->quantity, $subsequentAcquisitions->quantity())) {
                $this->recordThat(new Section104PoolTokenDisposalReverted(
                    section104PoolId: $disposal->section104PoolId,
                    date: $disposal->date,
                    quantity: $disposal->quantity,
                    costBasis: $disposal->costBasis,
                ));
                $disposalsToReplay[] = $disposal;
            }
            // Stop as soon as a disposal had its entire quantity covered by future acquisitions
        }

        $this->recordThat(new Section104PoolTokenAcquired(
            section104PoolId: $action->section104PoolId,
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $action->costBasis,
        ));

        // Replay original disposal events
        foreach (array_reverse($disposalsToReplay) as $disposal) {
            $this->disposeOf(new DisposeOfSection104PoolToken(
                section104PoolId: $disposal->section104PoolId,
                date: $disposal->date,
                quantity: $disposal->quantity,
                disposalProceeds: $disposal->disposalProceeds,
            ));
        }
    }

    public function applySection104PoolTokenAcquired(Section104PoolTokenAcquired $event): void
    {
        $newQuantity = Math::add($this->quantity, $event->quantity);
        $previousCostBasis = $this->costBasis ?? new FiatAmount('0', $event->costBasis->currency);
        $newCostBasis = $previousCostBasis->plus($event->costBasis);
        $newAverageCostBasisPerUnit = $newCostBasis->dividedBy($newQuantity);

        $this->quantity = $newQuantity;
        $this->costBasis = $newCostBasis;
        $this->averageCostBasisPerUnit = $newAverageCostBasisPerUnit;

        $this->transactions->add(new Section104PoolAcquisition(
            date: $event->date,
            quantity: $event->quantity,
            costBasis: $event->costBasis,
            quantityMatchedForPooling: '0',
        ));
    }

    public function applySection104PoolTokenDisposalReverted(Section104PoolTokenDisposalReverted $event): void
    {
        $newQuantity = Math::add($this->quantity, $event->quantity);
        $newCostBasis = $this->costBasis->plus($event->costBasis);

        $this->quantity = $newQuantity;
        $this->costBasis = $newCostBasis;

        // Delete disposals of that day?
    }

    /** @throws Section104PoolException */
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
            throw Section104PoolException::insufficientQuantityAvailable(
                section104PoolId: $action->section104PoolId,
                disposalQuantity: $action->quantity,
                availableQuantity: $this->quantity,
            );
        }

        assert(! is_null($this->averageCostBasisPerUnit));

        $costBasis = DisposalCostBasisCalculator::calculate(
            action: $action,
            transactions: $this->transactions->copy(),
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
        $newCostBasis = $this->costBasis->minus($this->averageCostBasisPerUnit->multipliedBy($event->quantity));

        $this->quantity = $newQuantity;
        $this->costBasis = $newCostBasis;

        $this->transactions->add(new Section104PoolDisposal(
            date: $event->date,
            quantity: $event->quantity,
            costBasis: $event->costBasis,
            disposalProceeds: $event->disposalProceeds,
        ));
    }
}
