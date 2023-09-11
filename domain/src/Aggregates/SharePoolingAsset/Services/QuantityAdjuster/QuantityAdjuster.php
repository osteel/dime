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

final class QuantityAdjuster
{
    /**
     * Adjust acquisition quantities based on the disposal's allocated quantities.
     *
     * @throws SharePoolingAssetAcquisitionException
     */
    public static function applyDisposal(
        SharePoolingAssetDisposal $disposal,
        SharePoolingAssetTransactions $transactions,
    ): void {
        foreach (self::getAcquisitions($disposal->sameDayQuantityAllocation, $transactions) as $acquisition) {
            $acquisition->increaseSameDayQuantity($disposal->sameDayQuantityAllocation->quantityAllocatedTo($acquisition));
        }

        foreach (self::getAcquisitions($disposal->thirtyDayQuantityAllocation, $transactions) as $acquisition) {
            $acquisition->increaseThirtyDayQuantity($disposal->thirtyDayQuantityAllocation->quantityAllocatedTo($acquisition));
        }
    }

    /**
     * Restore acquisition quantities that were previously allocated to a disposal that is now being reverted.
     *
     * @throws SharePoolingAssetAcquisitionException
     */
    public static function revertDisposal(
        SharePoolingAssetDisposal $disposal,
        SharePoolingAssetTransactions $transactions,
    ): void {
        foreach (self::getAcquisitions($disposal->sameDayQuantityAllocation, $transactions) as $acquisition) {
            $acquisition->decreaseSameDayQuantity($disposal->sameDayQuantityAllocation->quantityAllocatedTo($acquisition));
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
