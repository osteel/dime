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

        // Deduct what's left from the same-day quantity yet to be matched
        $unmatchedQuantity = $sameDayDisposals->quantity()->minus($sameDayDisposals->sameDayQuantity());

        $remainingQuantity = $remainingQuantity->minus(Quantity::minimum($remainingQuantity, $unmatchedQuantity));

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

        // Go through disposals of the past 30 days with quantity not matched with same-day acquisitions
        // or acquisitions made within the next 30 days, a.k.a disposals with section 104 pool quantity
        $past30DaysDisposals = $transactions->disposalsMadeBetween($date->minusDays(30), $date)
            ->withSection104PoolQuantity();

        foreach ($past30DaysDisposals as $disposal) {
            $disposalsToRevert->add($disposal);

            $quantityToApply = Quantity::minimum($remainingQuantity, $disposal->getSection104PoolQuantity());
            $remainingQuantity = $remainingQuantity->minus($quantityToApply);

            // Stop as soon as a disposal had its entire quantity covered by future acquisitions
            if ($remainingQuantity->isZero()) {
                break;
            }
        }

        return $disposalsToRevert;
    }
}
