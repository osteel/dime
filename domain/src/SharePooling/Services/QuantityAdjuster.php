<?php

namespace Domain\SharePooling\Services;

use Domain\SharePooling\Services\Exceptions\QuantityAdjusterException;
use Domain\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;

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

    /**
     * @return array<int, SharePoolingTokenAcquisition>
     * @throws QuantityAdjusterException
     */
    private static function getAcquisitions(
        QuantityBreakdown $breakdown,
        SharePoolingTransactions $transactions,
    ): array {
        $acquisitions = [];

        foreach ($breakdown->positions() as $position) {
            if (is_null($acquisition = $transactions->get($position))) {
                QuantityAdjusterException::transactionNotFound($position);
            }

            if (! $acquisition instanceof SharePoolingTokenAcquisition) {
                QuantityAdjusterException::notAnAcquisition($position);
            }

            $acquisitions[] = $acquisition;
        }

        return $acquisitions;
    }
}
