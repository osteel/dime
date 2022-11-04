<?php

namespace Domain\SharePooling\Services;

use Domain\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\Services\Math\Math;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;

final class SharePoolingTokenDisposalProcessor
{
    public static function process(
        DisposeOfSharePoolingToken $action,
        SharePoolingTransactions $transactions,
    ): SharePoolingTokenDisposal {
        $costBasis = $action->disposalProceeds->nilAmount();
        $remainingQuantity = $action->quantity;
        $nilAmount = $action->disposalProceeds->nilAmount();

        // Get acquisitions made before the disposal
        $priorAcquisitions = $transactions->acquisitionsMadeBefore($action->date);
        $averageCostBasisPerUnit = $priorAcquisitions->section104PoolAverageCostBasisPerUnit() ?? $nilAmount;

        // Find out if the asset has been acquired on the same day
        $sameDayAcquisitions = $transactions->acquisitionsMadeOn($action->date);
        $sameDayQuantity = '0';

        // Get the average cost basis
        if ($sameDayAcquisitionsAverageCostBasis = $sameDayAcquisitions->averageCostBasisPerUnit()) {
            // Apply this average cost basis to the disposed of asset, up to the quantity acquired that day
            $quantityToApply = Math::min($sameDayAcquisitions->quantity(), $remainingQuantity);
            $costBasis = $costBasis->plus($sameDayAcquisitionsAverageCostBasis->multipliedBy($quantityToApply));
            $remainingQuantity = Math::sub($remainingQuantity, $quantityToApply);
            // Deduct the applied quantity from the same-day acquisitions
            $remainder = $quantityToApply;
            foreach ($sameDayAcquisitions as $acquisition) {
                $remainder = $acquisition->increaseSameDayQuantity($remainder);
                if (! Math::gt($remainder, '0')) {
                    break;
                }
            }
            // If not all of the same-day quantity has been matched, use the rest to update the pool's average cost basis per unit
            $remainingSameDayQuantity = Math::sub($sameDayAcquisitions->quantity(), $quantityToApply);
            if (Math::gt($remainingSameDayQuantity, '0')) {
                $averageCostBasisPerUnit = $sameDayAcquisitionsAverageCostBasis
                    ->multipliedBy($remainingSameDayQuantity)
                    ->plus($priorAcquisitions->section104PoolAverageCostBasisPerUnit() ?? $nilAmount)
                    ->dividedBy(Math::add($remainingSameDayQuantity, $priorAcquisitions->section104PoolQuantity()));
            }
            $sameDayQuantity = $quantityToApply;
        }

        $thirtyDayQuantity = '0';

        // If the quantity of disposed of tokens is greater than the quantity acquired that day
        if (Math::gt($remainingQuantity, '0')) {
            // Find out if the asset has been acquired in the next 30 days, keeping only the acquisitions
            // with remaining quantity after same-day disposals have been deducted
            $within30DaysAcquisitions = $transactions->acquisitionsMadeBetween($action->date->plusDays(1), $action->date->plusDays(30));
            // For each of these transactions, ordered by date ASC (FIFO)
            foreach ($within30DaysAcquisitions as $acquisition) {
                // Apply the transaction's cost basis to the disposed of asset up to the acquired quantity
                $quantityToApply = Math::min($acquisition->section104PoolQuantity, $remainingQuantity);
                // @TODO the quantity from disposals on the same day as the acquisition should be deducted
                // from the latter... somehow, but shouldn't actually update the acquisition stored in
                // the aggregate's transactions, because it's possible disposals of the same day as
                // the accquisition still need to be replayed. E.g. it should work on a copy
                $costBasis = $costBasis->plus($acquisition->averageCostBasisPerUnit()->multipliedBy($quantityToApply));
                $thirtyDayQuantity = Math::add($thirtyDayQuantity, $quantityToApply);
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

        return new SharePoolingTokenDisposal(
            date: $action->date,
            quantity: $action->quantity,
            costBasis: $costBasis,
            disposalProceeds: $action->disposalProceeds,
            sameDayQuantity: $sameDayQuantity,
            thirtyDayQuantity: $thirtyDayQuantity,
            section104PoolQuantity: $remainingQuantity,
        );
    }
}
