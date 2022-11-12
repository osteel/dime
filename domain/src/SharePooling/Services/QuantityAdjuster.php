<?php

namespace Domain\SharePooling\Services;

use Domain\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;

final class QuantityAdjuster
{
    public static function restoreAcquisitionQuantities(
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

    private static function getAcquisitions(
        QuantityBreakdown $breakdown,
        SharePoolingTransactions $transactions,
    ): array {
        $acquisitions = [];

        foreach ($breakdown->positions() as $position) {
            if (is_null($acquisition = $transactions->get($position))) {
                // @TODO move to proper exception
                throw new \Exception(sprintf('No transaction at position %s', $position));
            }

            if (! $acquisition instanceof SharePoolingTokenAcquisition) {
                // @TODO move to proper exception
                throw new \Exception(sprintf('Transaction at position %s is not an acquisition', $position));
            }

            $acquisitions[] = $acquisition;
        }

        return $acquisitions;
    }
}
