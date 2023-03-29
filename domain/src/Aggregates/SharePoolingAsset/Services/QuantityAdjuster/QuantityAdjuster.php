<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster;

use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\Exceptions\QuantityAdjusterException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions\SharePoolingAssetAcquisitionException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetAcquisitions;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactions;

/**
 * This service restores the quantities from acquisitions that were
 * previously matched with a disposal that is now being reverted.
 */
final class QuantityAdjuster
{
    public static function revertDisposal(
        SharePoolingAssetDisposal $disposal,
        SharePoolingAssetTransactions $transactions,
    ): void {
        foreach (self::getAcquisitions($disposal->sameDayQuantityAllocation, $transactions) as $acquisition) {
            try {
                $acquisition->decreaseSameDayQuantity($disposal->sameDayQuantityAllocation->quantityAllocatedTo($acquisition));
            } catch (SharePoolingAssetAcquisitionException) {
                // @TODO When re-acquiring within 30 days an asset that was disposed of on the same day it was acquired,
                // decreasing the same-day quantity of the concerned acquisitions fails, because at the time the latter
                // were recorded within the SharePoolingAssetAcquired events that had no same-day quantity yet
            }
        }

        foreach (self::getAcquisitions($disposal->thirtyDayQuantityAllocation, $transactions) as $acquisition) {
            $acquisition->decreaseThirtyDayQuantity($disposal->thirtyDayQuantityAllocation->quantityAllocatedTo($acquisition));
        }
    }

    /** @throws QuantityAdjusterException */
    private static function getAcquisitions(
        QuantityAllocation $allocation,
        SharePoolingAssetTransactions $transactions,
    ): SharePoolingAssetAcquisitions {
        $acquisitions = SharePoolingAssetAcquisitions::make();

        foreach ($allocation->positions() as $position) {
            if (is_null($acquisition = $transactions->get($position))) {
                throw QuantityAdjusterException::transactionNotFound($position);
            }

            if (! $acquisition instanceof SharePoolingAssetAcquisition) {
                throw QuantityAdjusterException::notAnAcquisition($position);
            }

            $acquisitions->add($acquisition);
        }

        return $acquisitions;
    }
}
