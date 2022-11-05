<?php

namespace Domain\SharePooling\Services;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final class SharePoolingTokenDisposalProcessor
{
    public static function process(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $quantity,
        FiatAmount $disposalProceeds
    ): SharePoolingTokenDisposal {
        $costBasis = $disposalProceeds->nilAmount();
        $remainingQuantity = new Quantity($quantity->quantity);

        [$sameDayQuantity, $costBasis, $averageCostBasisPerUnit] = self::processSameDayAcquisitions($transactions, $date, $remainingQuantity, $costBasis);

        $remainingQuantity = $remainingQuantity->minus($sameDayQuantity);

        [$thirtyDayQuantity, $costBasis] = self::processAcquisitionsWithin30Days($transactions, $date, $remainingQuantity, $costBasis);

        $remainingQuantity = $remainingQuantity->minus($thirtyDayQuantity);

        // If there are still some disposed of tokens left
        if ($remainingQuantity->isGreaterThan('0')) {
            // Apply the section 104 pool's average cost basis per unit to the remainder
            $costBasis = $costBasis->plus($averageCostBasisPerUnit->multipliedBy($remainingQuantity));
        }

        return new SharePoolingTokenDisposal(
            date: $date,
            quantity: new Quantity($quantity->quantity),
            costBasis: $costBasis,
            disposalProceeds: $disposalProceeds,
            sameDayQuantity: $sameDayQuantity,
            thirtyDayQuantity: $thirtyDayQuantity,
            section104PoolQuantity: $remainingQuantity,
        );
    }

    private static function processSameDayAcquisitions(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): array {
        $nilAmount = $costBasis->nilAmount();

        // Get acquisitions made before the disposal
        $priorAcquisitions = $transactions->acquisitionsMadeBefore($date);
        $averageCostBasisPerUnit = $priorAcquisitions->section104PoolAverageCostBasisPerUnit() ?? $nilAmount;

        // Find out if the asset has been acquired on the same day
        $sameDayAcquisitions = $transactions->acquisitionsMadeOn($date);
        $sameDayQuantity = new Quantity('0');

        // Get the average cost basis
        if (is_null($sameDayAcquisitionsAverageCostBasis = $sameDayAcquisitions->averageCostBasisPerUnit())) {
            return [$sameDayQuantity, $costBasis, $averageCostBasisPerUnit];
        }

        // Apply this average cost basis to the disposed of asset, up to the quantity acquired that day
        $quantityToApply = Quantity::minimum($sameDayAcquisitions->quantity(), $remainingQuantity);
        $costBasis = $costBasis->plus($sameDayAcquisitionsAverageCostBasis->multipliedBy($quantityToApply));

        // Deduct the applied quantity from the same-day acquisitions
        $remainder = $quantityToApply;
        foreach ($sameDayAcquisitions as $acquisition) {
            $remainder = $acquisition->increaseSameDayQuantity($remainder);
            if ($remainder->isZero()) {
                break;
            }
        }

        // If not all of the same-day quantity has been matched, use the rest to update the pool's average cost basis per unit
        $remainingSameDayQuantity = $sameDayAcquisitions->quantity()->minus($quantityToApply);

        if ($remainingSameDayQuantity->isGreaterThan('0')) {
            $averageCostBasisPerUnit = $sameDayAcquisitionsAverageCostBasis
                ->multipliedBy($remainingSameDayQuantity)
                // @TODO ??? I get that we recalculate the average cost basis per unit because we've updated the
                // quantities of the acquisitions above, but surely we should multiply that amount by something?
                ->plus($priorAcquisitions->section104PoolAverageCostBasisPerUnit() ?? $nilAmount)
                ->dividedBy($remainingSameDayQuantity->plus($priorAcquisitions->section104PoolQuantity()));
        }

        $sameDayQuantity = $quantityToApply;

        return [$sameDayQuantity, $costBasis, $averageCostBasisPerUnit];
    }

    private static function processAcquisitionsWithin30Days(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): array {
        $thirtyDayQuantity = new Quantity('0');

        if ($remainingQuantity->isZero()) {
            return [$thirtyDayQuantity, $costBasis];
        }

        // Find out if the asset has been acquired in the next 30 days, keeping only the acquisitions
        // with remaining quantity after same-day disposals have been deducted
        $within30DaysAcquisitions = $transactions->acquisitionsMadeBetween($date->plusDays(1), $date->plusDays(30));

        // For each of these transactions, ordered by date ASC (FIFO)
        foreach ($within30DaysAcquisitions as $acquisition) {
            // Apply the transaction's cost basis to the disposed of asset up to the acquired quantity
            $quantityToApply = Quantity::minimum($acquisition->section104PoolQuantity, $remainingQuantity);
            // @TODO the quantity from disposals on the same day as the acquisition should be deducted
            // from the latter... somehow, but shouldn't actually update the acquisition stored in
            // the aggregate's transactions, because it's possible disposals of the same day as
            // the acquisition still need to be replayed. E.g. it should work on a copy. Or
            // should it? Should subsequent disposals be able to revert previous disposals
            // as well, if they happen on the same day as an acquisition that was within
            // 30 days of a previous disposal? Because if the 30-day quantities aren't
            // deducted from the acquisitions now, when will they be?
            $costBasis = $costBasis->plus($acquisition->averageCostBasisPerUnit()->multipliedBy($quantityToApply));
            $thirtyDayQuantity = $thirtyDayQuantity->plus($quantityToApply);
            $remainingQuantity = $remainingQuantity->minus($quantityToApply);

            // Continue until there are no more transactions or we've covered all disposed tokens
            if ($remainingQuantity->isZero()) {
                break;
            }
        }

        return [$thirtyDayQuantity, $costBasis];
    }
}
