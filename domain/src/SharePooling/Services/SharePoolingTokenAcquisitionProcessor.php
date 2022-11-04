<?php

namespace Domain\SharePooling\Services;

use Domain\Services\Math\Math;
use Domain\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;

final class SharePoolingTokenAcquisitionProcessor
{
    public static function getSharePoolingTokenDisposalsToRevert(
        AcquireSharePoolingToken $action,
        SharePoolingTransactions $transactions,
    ): SharePoolingTokenDisposals {
        $disposalsToReplay = SharePoolingTokenDisposals::make();
        $remainingQuantity = $action->quantity;

        // Get same-day disposals
        $sameDayDisposals = $transactions->disposalsMadeOn($action->date);
        // Revert them if their quantity not matched with same-day acquisitions is less than their total quantity
        if (Math::gt($sameDayDisposals->quantity(), $sameDayDisposals->sameDayQuantity())) {
            $disposalsToReplay->add(...$sameDayDisposals);
            $unmatchedQuantity = Math::sub($sameDayDisposals->quantity(), $sameDayDisposals->sameDayQuantity());
            $remainingQuantity = Math::sub($remainingQuantity, Math::min($remainingQuantity, $unmatchedQuantity));
        }

        if (Math::gt($remainingQuantity, '0')) {
            // Go through disposals in the past 30 days
            $past30DaysDisposals = $transactions->disposalsMadeBetween($action->date->minusDays(30), $action->date);

            foreach ($past30DaysDisposals as $disposal) {
                // Revert the ones whose quantities not matched with same-day acquisitions or within-30-day acquisitions are less than their total quantity
                if (Math::gt($disposal->quantity, Math::add($disposal->sameDayQuantity, $disposal->thirtyDayQuantity))) {
                    $disposalsToReplay->add($disposal);
                    $unmatchedQuantity = Math::sub($disposal->quantity, Math::add($disposal->sameDayQuantity, $disposal->thirtyDayQuantity));
                    $remainingQuantity = Math::sub($remainingQuantity, Math::min($remainingQuantity, $unmatchedQuantity));
                    // Stop as soon as a disposal had its entire quantity covered by future acquisitions
                    if (! Math::gt($remainingQuantity, '0')) {
                        break;
                    }
                }
            }
        }

        return $disposalsToReplay;
    }
}
