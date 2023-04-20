<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster;

use Domain\Aggregates\SharePoolingAsset\Entities\Exceptions\SharePoolingAssetAcquisitionException;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisitions;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\Exceptions\QuantityAdjusterException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;

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

        foreach ($allocation->transactionIds() as $transactionId) {
            if (is_null($acquisition = $transactions->getForId($transactionId))) {
                throw QuantityAdjusterException::transactionNotFound($transactionId);
            }

            if (! $acquisition instanceof SharePoolingAssetAcquisition) {
                throw QuantityAdjusterException::notAnAcquisition($transactionId);
            }

            $acquisitions->add($acquisition);
        }

        return $acquisitions;
    }
}
