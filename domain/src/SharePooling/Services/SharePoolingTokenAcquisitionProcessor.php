<?php

namespace Domain\SharePooling\Services;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\ValueObjects\Quantity;

final class SharePoolingTokenAcquisitionProcessor
{
    public static function getSharePoolingTokenDisposalsToRevert(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $availableSameDayQuantity,
    ): SharePoolingTokenDisposals {
        $disposalsToRevert = SharePoolingTokenDisposals::make();

        $availableSameDayQuantity = self::addSameDayDisposalsToRevert(
            $disposalsToRevert,
            $transactions,
            $date,
            $availableSameDayQuantity,
        );

        self::add30DayDisposalsToRevert($disposalsToRevert, $transactions, $date, $availableSameDayQuantity);

        return $disposalsToRevert;
    }

    private static function addSameDayDisposalsToRevert(
        SharePoolingTokenDisposals $disposalsToRevert,
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $availableSameDayQuantity,
    ): Quantity {
        if ($availableSameDayQuantity->isZero()) {
            return $availableSameDayQuantity;
        }

        // Get same-day disposals with part of their quantity not matched with same-day acquisitions
        $sameDayDisposals = $transactions->disposalsMadeOn($date)->withAvailableSameDayQuantity();

        if ($sameDayDisposals->isEmpty()) {
            return $availableSameDayQuantity;
        }

        // As the average cost basis of same-day acquisitions is used to calculate the
        // cost basis of the disposals, it's simpler to revert them all and start over
        $disposalsToRevert->add(...$sameDayDisposals);

        // Deduct what's left from the same-day quantity yet to be matched
        $unmatchedQuantity = $sameDayDisposals->quantity()->minus($sameDayDisposals->sameDayQuantity());

        return $availableSameDayQuantity->minus(Quantity::minimum($availableSameDayQuantity, $unmatchedQuantity));
    }

    private static function add30DayDisposalsToRevert(
        SharePoolingTokenDisposals $disposalsToRevert,
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $availableSameDayQuantity,
    ): Quantity {
        if ($availableSameDayQuantity->isZero()) {
            return $availableSameDayQuantity;
        }

        // Go through disposals of the past 30 days with quantity not matched with same-day acquisitions
        // or acquisitions made within the next 30 days, a.k.a disposals with section 104 pool quantity
        $past30DaysDisposals = $transactions->disposalsMadeBetween($date->minusDays(30), $date)
            ->withSection104PoolQuantity();

        foreach ($past30DaysDisposals as $disposal) {
            $disposalsToRevert->add($disposal);

            $quantityToApply = Quantity::minimum($availableSameDayQuantity, $disposal->section104PoolQuantity);
            $availableSameDayQuantity = $availableSameDayQuantity->minus($quantityToApply);

            // Stop as soon as a disposal had its entire quantity covered by future acquisitions
            if ($availableSameDayQuantity->isZero()) {
                break;
            }
        }

        return $availableSameDayQuantity;
    }
}
