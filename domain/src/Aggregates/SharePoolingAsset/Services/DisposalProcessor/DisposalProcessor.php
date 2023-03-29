<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Services\DisposalProcessor;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactions;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

/**
 * This service essentially calculates the cost basis of a disposal by looking at past and future
 * transactions, following the various share pooling asset rules (same-day, 30-day, section 104 pool).
 */
final class DisposalProcessor
{
    public static function process(
        DisposeOfSharePoolingAsset $disposal,
        SharePoolingAssetTransactions $transactions,
        ?int $position,
    ): SharePoolingAssetDisposal {
        $sameDayQuantityAllocation = new QuantityAllocation();
        $thirtyDayQuantityAllocation = new QuantityAllocation();

        $costBasis = self::calculateCostBasis(
            transactions: $transactions,
            date: $disposal->date,
            sameDayQuantityAllocation: $sameDayQuantityAllocation,
            thirtyDayQuantityAllocation: $thirtyDayQuantityAllocation,
            remainingQuantity: $disposal->quantity,
        );

        // Disposals being replayed already have a position, in which case we restore
        // that position to make sure the disposal is inserted back where it should
        return (new SharePoolingAssetDisposal(
            date: $disposal->date,
            quantity: $disposal->quantity,
            costBasis: $costBasis,
            proceeds: $disposal->proceeds,
            sameDayQuantityAllocation: $sameDayQuantityAllocation,
            thirtyDayQuantityAllocation: $thirtyDayQuantityAllocation,
        ))->setPosition($position);
    }

    private static function calculateCostBasis(
        SharePoolingAssetTransactions $transactions,
        LocalDate $date,
        QuantityAllocation $sameDayQuantityAllocation,
        QuantityAllocation $thirtyDayQuantityAllocation,
        Quantity $remainingQuantity,
    ): FiatAmount {
        assert($transactions->first() !== null);

        $costBasis = $transactions->first()->costBasis->zero();

        return self::processSameDayAcquisitions(
            $transactions,
            $date,
            $sameDayQuantityAllocation,
            $thirtyDayQuantityAllocation,
            $remainingQuantity,
            $costBasis,
        );
    }

    private static function processSameDayAcquisitions(
        SharePoolingAssetTransactions $transactions,
        LocalDate $date,
        QuantityAllocation $sameDayQuantityAllocation,
        QuantityAllocation $thirtyDayQuantityAllocation,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): FiatAmount {
        if ($remainingQuantity->isZero()) {
            return $costBasis;
        }

        // Get same-day acquisitions with some quantity not matched with same-day disposals yet
        $sameDayAcquisitions = $transactions->acquisitionsMadeOn($date)->withAvailableSameDayQuantity();

        if ($sameDayAcquisitions->isEmpty()) {
            return self::processAcquisitionsWithinThirtyDays(
                $transactions,
                $date,
                $thirtyDayQuantityAllocation,
                $remainingQuantity,
                $costBasis,
            );
        }

        // Get the same-day average cost basis per unit, of all acquisitions of that day (not
        // just the ones with available same-day quantity). We want the absolute value here,
        // regardless of other types of matching, because same-day matching gets priority
        $sameDayAcquisitionsAverageCostBasisPerUnit = $transactions->acquisitionsMadeOn($date)->averageCostBasisPerUnit();

        assert($sameDayAcquisitionsAverageCostBasisPerUnit !== null);

        // Apply this average cost basis to the disposed of asset, up to the
        // quantity acquired that day not yet matched with same-day disposals
        $availableSameDayQuantity = Quantity::minimum($sameDayAcquisitions->availableSameDayQuantity(), $remainingQuantity);
        $costBasis = $costBasis->plus($sameDayAcquisitionsAverageCostBasisPerUnit->multipliedBy($availableSameDayQuantity));

        // Deduct the applied quantity from the same-day acquisitions
        $remainder = $availableSameDayQuantity;
        foreach ($sameDayAcquisitions as $acquisition) {
            $quantityToAllocate = Quantity::minimum($remainder, $acquisition->availableSameDayQuantity());
            $sameDayQuantityAllocation->allocateQuantity($quantityToAllocate, $acquisition);
            $acquisition->increaseSameDayQuantity($remainder);
            $remainder =  $remainder->minus($quantityToAllocate);
            if ($remainder->isZero()) {
                break;
            }
        }

        $remainingQuantity = $remainingQuantity->minus($availableSameDayQuantity);

        return self::processAcquisitionsWithinThirtyDays(
            $transactions,
            $date,
            $thirtyDayQuantityAllocation,
            $remainingQuantity,
            $costBasis,
        );
    }

    private static function processAcquisitionsWithinThirtyDays(
        SharePoolingAssetTransactions $transactions,
        LocalDate $date,
        QuantityAllocation $thirtyDayQuantityAllocation,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): FiatAmount {
        if ($remainingQuantity->isZero()) {
            return $costBasis;
        }

        // Get acquisitions with available 30-day quantity, made within 30 days
        $withinThirtyDaysAcquisitions = $transactions->acquisitionsMadeBetween($date->plusDays(1), $date->plusDays(30))
            ->withAvailableThirtyDayQuantity();

        foreach ($withinThirtyDaysAcquisitions as $acquisition) {
            // Apply the acquisition's cost basis to the disposed of asset up to the remaining quantity
            $thirtyDayQuantityToApply = Quantity::minimum($acquisition->availableThirtyDayQuantity(), $remainingQuantity);

            // Also deduct same-day disposals with available same-day quantity that haven't been processed yet
            $sameDayDisposals = $transactions->disposalsMadeOn($acquisition->date)
                ->unprocessed()
                ->withAvailableSameDayQuantity();

            foreach ($sameDayDisposals as $disposal) {
                $sameDayQuantityToApply = Quantity::minimum($disposal->availableSameDayQuantity(), $thirtyDayQuantityToApply);
                $disposal->sameDayQuantityAllocation->allocateQuantity($sameDayQuantityToApply, $acquisition);
                $acquisition->increaseSameDayQuantity($sameDayQuantityToApply);
                $thirtyDayQuantityToApply = $thirtyDayQuantityToApply->minus($sameDayQuantityToApply);
                if ($thirtyDayQuantityToApply->isZero()) {
                    break;
                }
            }

            if ($thirtyDayQuantityToApply->isZero()) {
                continue;
            }

            $averageCostBasisPerUnit = $acquisition->averageCostBasisPerUnit();

            $costBasis = $averageCostBasisPerUnit
                ? $costBasis->plus($averageCostBasisPerUnit->multipliedBy($thirtyDayQuantityToApply))
                : $costBasis;

            $thirtyDayQuantityAllocation->allocateQuantity($thirtyDayQuantityToApply, $acquisition);
            $acquisition->increaseThirtyDayQuantity($thirtyDayQuantityToApply);
            $remainingQuantity = $remainingQuantity->minus($thirtyDayQuantityToApply);

            // Continue until there are no more transactions or we've covered all disposed tokens
            if ($remainingQuantity->isZero()) {
                break;
            }
        }

        return self::processSection104PoolAcquisitions($transactions, $date, $remainingQuantity, $costBasis);
    }

    private static function processSection104PoolAcquisitions(
        SharePoolingAssetTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): FiatAmount {
        if ($remainingQuantity->isZero()) {
            return $costBasis;
        }

        // Get acquisitions made before or on the day of the disposal
        if (($priorAcquisitions = $transactions->acquisitionsMadeBeforeOrOn($date))->isEmpty()) {
            return $costBasis;
        }

        // Get the average cost basis per unit for the quantity that went to the section 104 pool
        $averageCostBasisPerUnit = $priorAcquisitions->averageSection104PoolCostBasisPerUnit();

        // Apply the section 104 pool's average cost basis per unit to the remainder
        $costBasis = $averageCostBasisPerUnit
            ? $costBasis->plus($averageCostBasisPerUnit->multipliedBy($remainingQuantity))
            : $costBasis;

        return $costBasis;
    }
}
