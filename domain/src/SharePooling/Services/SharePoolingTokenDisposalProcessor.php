<?php

namespace Domain\SharePooling\Services;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\QuantityBreakdown;
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
        FiatAmount $disposalProceeds,
        ?int $position,
    ): SharePoolingTokenDisposal {
        [
            $costBasis,
            $sameDayQuantity,
            $thirtyDayQuantity,
        ] = self::computeDisposalProperties($transactions, $date, $quantity);

        $disposal = new SharePoolingTokenDisposal(
            date: $date,
            quantity: new Quantity($quantity->quantity),
            costBasis: $costBasis,
            disposalProceeds: $disposalProceeds,
            sameDayQuantity: $sameDayQuantity,
            thirtyDayQuantity: $thirtyDayQuantity,
        );

        // Disposals being replayed already have a position, in which case we restore
        // that position to make sure the disposal is inserted back where it should
        return is_null($position) ? $disposal : $disposal->setPosition($position);
    }

    private static function computeDisposalProperties(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
    ): array {
        $costBasis = $transactions->first()->costBasis->nilAmount();

        return self::processSameDayAcquisitions($transactions, $date, $remainingQuantity, $costBasis);
    }

    private static function processSameDayAcquisitions(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): array {
        if ($remainingQuantity->isZero()) {
            return [$costBasis, new QuantityBreakdown(), new QuantityBreakdown()];
        }

        // Get same-day acquisitions with some quantity not matched with same-day disposals yet
        $sameDayAcquisitions = $transactions->acquisitionsMadeOn($date)->withAvailableSameDayQuantity();

        if ($sameDayAcquisitions->isEmpty()) {
            return self::processAcquisitionsWithin30Days(
                $transactions,
                $date,
                $remainingQuantity,
                $costBasis,
                new QuantityBreakdown(),
            );
        }

        $sameDayQuantity = new QuantityBreakdown();

        // Get the same-day average cost basis per unit
        $sameDayAcquisitionsAverageCostBasisPerUnit = $sameDayAcquisitions->averageCostBasisPerUnit();

        // Apply this average cost basis to the disposed of asset, up to the
        // quantity acquired that day not yet matched with same-day disposals
        $availableSameDayQuantity = Quantity::minimum($sameDayAcquisitions->availableSameDayQuantity(), $remainingQuantity);
        $costBasis = $costBasis->plus($sameDayAcquisitionsAverageCostBasisPerUnit->multipliedBy($availableSameDayQuantity));

        // Deduct the applied quantity from the same-day acquisitions
        $remainder = $availableSameDayQuantity;
        foreach ($sameDayAcquisitions as $acquisition) {
            $sameDayQuantity->assignQuantity($remainder, $acquisition);
            $remainder = $acquisition->increaseSameDayQuantity($remainder);
            if ($remainder->isZero()) {
                break;
            }
        }

        $remainingQuantity = $remainingQuantity->minus($availableSameDayQuantity);

        return self::processAcquisitionsWithin30Days(
            $transactions,
            $date,
            $remainingQuantity,
            $costBasis,
            $sameDayQuantity,
        );
    }

    private static function processAcquisitionsWithin30Days(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
        QuantityBreakdown $sameDayQuantity,
    ): array {
        if ($remainingQuantity->isZero()) {
            return [$costBasis, $sameDayQuantity, Quantity::zero()];
        }

        $thirtyDayQuantity = new QuantityBreakdown();

        // Find out if the asset has been acquired in the next 30 days, keeping only the acquisitions
        // with remaining quantity after same-day disposals have been deducted
        $within30DaysAcquisitions = $transactions->acquisitionsMadeBetween($date->plusDays(1), $date->plusDays(30));

        // For each of these transactions, ordered by date ASC (FIFO)
        foreach ($within30DaysAcquisitions as $acquisition) {
            // Apply the transaction's cost basis to the disposed of asset up to the acquired quantity
            $quantityToApply = Quantity::minimum($acquisition->section104PoolQuantity, $remainingQuantity);
            // @TODO the quantity from disposals on the same day as the acquisition (that haven't been matched with
            // an acquisition yet – the ones that were reverted, basically – use the `processed` boolean)
            // should be deducted from the acquisition somehow, but shouldn't actually update the acquisition
            // as stored in the aggregate's transactions, because these disposals are yet to be replayed.
            // If the current disposal already has a position (i.e. it's being replayed), the disposal
            // at that position should be ruled out from the acquisition's same-day disposals.
            $costBasis = $costBasis->plus($acquisition->averageCostBasisPerUnit()->multipliedBy($quantityToApply));
            $thirtyDayQuantity->assignQuantity($quantityToApply, $acquisition);
            $remainingQuantity = $remainingQuantity->minus($quantityToApply);

            // Continue until there are no more transactions or we've covered all disposed tokens
            if ($remainingQuantity->isZero()) {
                break;
            }
        }

        return self::processSection104PoolAcquisitions(
            $transactions,
            $date,
            $remainingQuantity,
            $costBasis,
            $sameDayQuantity,
            $thirtyDayQuantity,
        );
    }

    private static function processSection104PoolAcquisitions(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
        QuantityBreakdown $sameDayQuantity,
        QuantityBreakdown $thirtyDayQuantity,
    ): array {
        if ($remainingQuantity->isZero()) {
            return [$costBasis, $sameDayQuantity, $thirtyDayQuantity];
        }

        // Get acquisitions made before or on the day of the disposal
        $priorAcquisitions = $transactions->acquisitionsMadeBeforeOrOn($date);

        if ($priorAcquisitions->isEmpty()) {
            return $costBasis;
        }

        // Get the average cost basis per unit for the quantity that went to the section 104 pool
        $averageCostBasisPerUnit = $priorAcquisitions->section104PoolAverageCostBasisPerUnit();

        // Apply the section 104 pool's average cost basis per unit to the remainder
        $costBasis = $costBasis->plus($averageCostBasisPerUnit->multipliedBy($remainingQuantity));

        return [$costBasis, $sameDayQuantity, $thirtyDayQuantity];
    }
}
