<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Services\ReversionFinder;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposals;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\ValueObjects\Quantity;

/** This service identifies and returns the disposals that need to be reverted upon a new acquisition or disposal. */
final class ReversionFinder
{
    public static function disposalsToRevertOnAcquisition(
        AcquireSharePoolingAsset $acquisition,
        SharePoolingAssetTransactions $transactions,
    ): SharePoolingAssetDisposals {
        $disposalsToRevert = SharePoolingAssetDisposals::make();

        return self::addSameDayDisposalsToRevert(
            disposalsToRevert: $disposalsToRevert,
            transactions: $transactions,
            date: $acquisition->date,
            remainingQuantity: $acquisition->quantity,
        );
    }

    private static function addSameDayDisposalsToRevert(
        SharePoolingAssetDisposals $disposalsToRevert,
        SharePoolingAssetTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
    ): SharePoolingAssetDisposals {
        if ($remainingQuantity->isZero()) {
            return $disposalsToRevert;
        }

        // Get same-day disposals with part of their quantity not allocated to same-day acquisitions
        $sameDayDisposals = $transactions->disposalsMadeOn($date)->withAvailableSameDayQuantity();

        if ($sameDayDisposals->isEmpty()) {
            return self::add30DayDisposalsToRevert($disposalsToRevert, $transactions, $date, $remainingQuantity);
        }

        // As the average cost basis of same-day acquisitions is used to calculate the
        // cost basis of the disposals, it's simpler to revert them all and start over
        $disposalsToRevert->add(...$sameDayDisposals);

        // Deduct what's left (either the whole remaining quantity or the disposals' unallocated
        // same-day quantity, whichever is smaller) from the remaining quantity to be allocated
        $quantityToDeduct = Quantity::minimum($remainingQuantity, $sameDayDisposals->availableSameDayQuantity());
        $remainingQuantity = $remainingQuantity->minus($quantityToDeduct);

        return self::add30DayDisposalsToRevert($disposalsToRevert, $transactions, $date, $remainingQuantity);
    }

    private static function add30DayDisposalsToRevert(
        SharePoolingAssetDisposals $disposalsToRevert,
        SharePoolingAssetTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
    ): SharePoolingAssetDisposals {
        if ($remainingQuantity->isZero()) {
            return $disposalsToRevert;
        }

        // Go through disposals of the past 30 days with available 30-day quantity
        $pastThirtyDaysDisposals = $transactions->disposalsMadeBetween($date->minusDays(30), $date)
            ->withAvailableThirtyDayQuantity();

        foreach ($pastThirtyDaysDisposals as $disposal) {
            $disposalsToRevert->add($disposal);

            // Deduct what's left (either the whole remaining quantity or the disposal's uncallocated
            // 30-day quantity, whichever is smaller) from the remaining quantity to be allocated
            $quantityToDeduct = Quantity::minimum($remainingQuantity, $disposal->availableThirtyDayQuantity());
            $remainingQuantity = $remainingQuantity->minus($quantityToDeduct);

            // Stop as soon as a disposal had its entire quantity covered by future acquisitions
            if ($remainingQuantity->isZero()) {
                break;
            }
        }

        return $disposalsToRevert;
    }

    public static function disposalsToRevertOnDisposal(
        DisposeOfSharePoolingAsset $disposal,
        SharePoolingAssetTransactions $transactions,
    ): SharePoolingAssetDisposals {
        $disposalsToRevert = SharePoolingAssetDisposals::make();

        // Get processed disposals with 30-day quantity allocated to acquisitions on the same
        // day as the disposal, with same-day quantity about to be allocated to the disposal
        $sameDayAcquisitions = $transactions->acquisitionsMadeOn($disposal->date)->withThirtyDayQuantity();

        $remainingQuantity = $disposal->quantity;
        foreach ($sameDayAcquisitions as $acquisition) {
            // Add disposals up to the disposal's quantity, starting with the most recent ones
            $disposalsWithAllocatedThirtyDayQuantity = $transactions->processed()
                ->disposalsWithThirtyDayQuantityAllocatedTo($acquisition)
                ->reverse();

            foreach ($disposalsWithAllocatedThirtyDayQuantity as $disposal) {
                $disposalsToRevert->add($disposal);

                $quantityToDeduct = Quantity::minimum($disposal->thirtyDayQuantityAllocatedTo($acquisition), $remainingQuantity);
                $remainingQuantity = $remainingQuantity->minus($quantityToDeduct);

                // Stop as soon as the disposal's quantity has fully been allocated
                if ($remainingQuantity->isZero()) {
                    break 2;
                }
            }
        }

        return $disposalsToRevert;
    }
}
