<?php

namespace Domain\SharePooling\Services;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\ValueObjects\Quantity;

/**
 * This service identifies and returns the disposals that need to be reverted upon a new acquisition or disposal.
 */
final class ReversionFinder
{
    public static function disposalsToRevertOnAcquisition(
        AcquireSharePoolingToken $acquisition,
        SharePoolingTransactions $transactions,
    ): SharePoolingTokenDisposals {
        $disposalsToRevert = SharePoolingTokenDisposals::make();

        return self::addSameDayDisposalsToRevert(
            disposalsToRevert: $disposalsToRevert,
            transactions: $transactions,
            date: $acquisition->date,
            remainingQuantity: $acquisition->quantity,
        );
    }

    private static function addSameDayDisposalsToRevert(
        SharePoolingTokenDisposals $disposalsToRevert,
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
    ): SharePoolingTokenDisposals {
        if ($remainingQuantity->isZero()) {
            return $disposalsToRevert;
        }

        // Get same-day disposals with part of their quantity not matched with same-day acquisitions
        $sameDayDisposals = $transactions->disposalsMadeOn($date)->withAvailableSameDayQuantity();

        if ($sameDayDisposals->isEmpty()) {
            return self::add30DayDisposalsToRevert($disposalsToRevert, $transactions, $date, $remainingQuantity);
        }

        // As the average cost basis of same-day acquisitions is used to calculate the
        // cost basis of the disposals, it's simpler to revert them all and start over
        $disposalsToRevert->add(...$sameDayDisposals);

        // Deduct what's left (either the whole remaining quantity or the disposals' unmatched
        // same-day quantity, whichever is smaller) from the remaining quantity to be matched
        $quantityToDeduct = Quantity::minimum($remainingQuantity, $sameDayDisposals->availableSameDayQuantity());
        $remainingQuantity = $remainingQuantity->minus($quantityToDeduct);

        return self::add30DayDisposalsToRevert($disposalsToRevert, $transactions, $date, $remainingQuantity);
    }

    private static function add30DayDisposalsToRevert(
        SharePoolingTokenDisposals $disposalsToRevert,
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
    ): SharePoolingTokenDisposals {
        if ($remainingQuantity->isZero()) {
            return $disposalsToRevert;
        }

        // Go through disposals of the past 30 days with available 30-day quantity
        $pastThirtyDaysDisposals = $transactions->disposalsMadeBetween($date->minusDays(30), $date)
            ->withAvailableThirtyDayQuantity();

        foreach ($pastThirtyDaysDisposals as $disposal) {
            $disposalsToRevert->add($disposal);

            // Deduct what's left (either the whole remaining quantity or the disposal's available
            // 30-day quantity, whichever is smaller) from the remaining quantity to be matched
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
        DisposeOfSharePoolingToken $disposal,
        SharePoolingTransactions $transactions,
    ): SharePoolingTokenDisposals {
        $disposalsToRevert = SharePoolingTokenDisposals::make();

        // Get processed disposals with 30-day quantity matched with acquisitions on the same
        // day as the disposal, with same-day quantity about to be matched with the disposal
        $sameDayAcquisitions = $transactions->acquisitionsMadeOn($disposal->date)->withThirtyDayQuantity();

        $remainingQuantity = $disposal->quantity;
        foreach ($sameDayAcquisitions as $acquisition) {
            // Add disposals up to the disposal's quantity, starting with the most recent ones
            $disposalsWithMatchedThirtyDayQuantity = $transactions->processed()
                ->disposalsWithThirtyDayQuantityMatchedWith($acquisition)
                ->reverse();

            foreach ($disposalsWithMatchedThirtyDayQuantity as $disposal) {
                $disposalsToRevert->add($disposal);

                $quantityToDeduct = Quantity::minimum($disposal->thirtyDayQuantityMatchedWith($acquisition), $remainingQuantity);
                $remainingQuantity = $remainingQuantity->minus($quantityToDeduct);

                // Stop as soon as the disposal's quantity has fully been matched
                if ($remainingQuantity->isZero()) {
                    break(2);
                }
            }
        }

        return $disposalsToRevert;
    }
}
