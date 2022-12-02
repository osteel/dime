<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Services\QuantityAdjuster;

use Domain\Aggregates\SharePooling\Services\QuantityAdjuster\Exceptions\QuantityAdjusterException;
use Domain\Aggregates\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenAcquisitions;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTransactions;

/**
 * This service restores the quantities from acquisitions that were
 * previously matched with a disposal that is now being reverted.
 */
final class QuantityAdjuster
{
    public static function revertDisposal(
        SharePoolingTokenDisposal $disposal,
        SharePoolingTransactions $transactions,
    ): void {
        foreach (self::getAcquisitions($disposal->sameDayQuantityBreakdown, $transactions) as $acquisition) {
            $acquisition->decreaseSameDayQuantity($disposal->sameDayQuantityBreakdown->quantityMatchedWith($acquisition));
        }

        foreach (self::getAcquisitions($disposal->thirtyDayQuantityBreakdown, $transactions) as $acquisition) {
            $acquisition->decreaseThirtyDayQuantity($disposal->thirtyDayQuantityBreakdown->quantityMatchedWith($acquisition));
        }
    }

    /** @throws QuantityAdjusterException */
    private static function getAcquisitions(
        QuantityBreakdown $breakdown,
        SharePoolingTransactions $transactions,
    ): SharePoolingTokenAcquisitions {
        $acquisitions = SharePoolingTokenAcquisitions::make();

        foreach ($breakdown->positions() as $position) {
            if (is_null($acquisition = $transactions->get($position))) {
                throw QuantityAdjusterException::transactionNotFound($position);
            }

            if (! $acquisition instanceof SharePoolingTokenAcquisition) {
                throw QuantityAdjusterException::notAnAcquisition($position);
            }

            $acquisitions->add($acquisition);
        }

        return $acquisitions;
    }
}
