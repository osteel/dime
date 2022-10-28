<?php

namespace Domain\Section104Pool\Services;

use Domain\Section104Pool\Actions\DisposeOfSection104PoolToken;
use Domain\Section104Pool\ValueObjects\Section104PoolTransactions;
use Domain\Services\Math\Math;
use Domain\ValueObjects\FiatAmount;

final class DisposalCostBasisCalculator
{
    public static function calculate(
        DisposeOfSection104PoolToken $action,
        Section104PoolTransactions $transactions,
    ): FiatAmount {
        $costBasis = new FiatAmount('0', $action->disposalProceeds->currency);
        $remainingQuantity = $action->quantity;
        $nilAmount = new FiatAmount('0', $action->disposalProceeds->currency);

        // Get acquisitions made before the disposal
        $priorAcquisitions = $transactions->acquisitionsMadeBefore($action->date);
        $averageCostBasisPerUnit = $priorAcquisitions?->averageCostBasisPerUnit() ?? $nilAmount;

        // Find out if the asset has been acquired on the same day
        $sameDayAcquisitions = $transactions->acquisitionsMadeOn($action->date);

        // Get the average cost basis
        if ($sameDayAcquisitionsAverageCostBasis = $sameDayAcquisitions->averageCostBasisPerUnit()) {
            // Apply this average cost basis to the disposed of asset, up to the quantity acquired that day
            $quantityToApply = Math::min($sameDayAcquisitions->quantity(), $remainingQuantity);
            $costBasis = $costBasis->plus($sameDayAcquisitionsAverageCostBasis->multipliedBy($quantityToApply));
            $remainingQuantity = Math::sub($remainingQuantity, $quantityToApply);
            // If not all of the same-day quantity has been matched, use the rest to update the pool's average cost basis per unit
            $remainingSameDayQuantity = Math::sub($sameDayAcquisitions->quantity(), $quantityToApply);
            if (Math::gt($remainingSameDayQuantity, '0')) {
                $averageCostBasisPerUnit = $sameDayAcquisitionsAverageCostBasis
                    ->multipliedBy($remainingSameDayQuantity)
                    ->plus($priorAcquisitions?->costBasis() ?? $nilAmount)
                    ->dividedBy(Math::add($remainingSameDayQuantity, $priorAcquisitions->quantity()));
            }
        }

        // If the quantity of disposed of tokens is greater than the quantity acquired that day
        if (Math::gt($remainingQuantity, '0')) {
            // Find out if the asset has been acquired in the next 30 days
            $within30DaysAcquisitions = $transactions->acquisitionsMadeBetween($action->date->plusDays(1), $action->date->plusDays(30));
            // For each of these transactions, ordered by date ASC (FIFO)
            foreach ($within30DaysAcquisitions as $acquisition) {
                // Apply the transaction's cost basis to the disposed of asset up to the acquired quantity
                $quantityToApply = Math::min($acquisition->quantity, $remainingQuantity);
                $costBasis = $costBasis->plus($acquisition->averageCostBasisPerUnit()->multipliedBy($quantityToApply));
                $remainingQuantity = Math::sub($remainingQuantity, $quantityToApply);
                // Continue until there are no more transactions or we've covered all disposed tokens
                if (Math::lte($remainingQuantity, '0')) {
                    break;
                }
            }
        }

        // If there are still some disposed of tokens left
        if (Math::gt($remainingQuantity, '0')) {
            // Apply the section 104 pool's average cost basis per unit to the remainder
            $costBasis = $costBasis->plus($averageCostBasisPerUnit->multipliedBy($remainingQuantity));
        }

        return $costBasis;
    }
}
