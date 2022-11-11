<?php

namespace Domain\SharePooling\Services;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\ValueObjects\Quantity;

final class SharePoolingTransactionFinder
{
    public static function getDisposalsToRevertAfterAcquisition(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $quantity,
    ): SharePoolingTokenDisposals {
        $disposalsToRevert = SharePoolingTokenDisposals::make();

        return self::addSameDayDisposalsToRevert($disposalsToRevert, $transactions, $date, $quantity);
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
}
