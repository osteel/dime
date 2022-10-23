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
        FiatAmount $averageCostBasisPerUnit,
    ): FiatAmount {
        $costBasis = new FiatAmount('0', $action->disposalProceeds->currency);
        $quantity = $action->quantity;

        // Find if the asset has been acquired on the same day
        $sameDayTransactions = $transactions->acquisitionsMadeOn($action->date);

        // Get the average cost basis
        if ($sameDayTransactionsAverageCostBasis = $sameDayTransactions->averageCostBasisPerUnit()) {
            // Apply this average cost basis to the disposed of asset, up to the quantity acquired that day
            $multiplier = Math::gt($sameDayTransactions->quantity(), $quantity) ? $quantity : $sameDayTransactions->quantity();
            $costBasis = $costBasis->plus($sameDayTransactionsAverageCostBasis->multipliedBy($multiplier));
            $quantity = Math::sub($quantity, $action->quantity);
        }

        // If the quantity of disposed of tokens is greater than the quantity acquired that day
        // Find if the asset has been acquired in the next 30 days
        // For each of these transactions, ordered by date ASC (FIFO)
        //    apply the transaction's cost basis to the disposed of asset up to the acquired quantity
        //    continue until there are no more transactions or we've covered all disposed tokens
        // If there are still some disposed of tokens left
        // Apply the section 104 pool's average cost basis per unit to the remainder
        if (Math::gt($quantity, '0')) {
            $costBasis = $costBasis->plus($averageCostBasisPerUnit->multipliedBy($quantity));
        }

        return $costBasis;
    }
}
