<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Services\DisposalProcessor;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

/**
 * This service essentially calculates the cost basis of a disposal by looking at past and future
 * transactions, following the various share pooling rules (same-day, 30-day, section 104 pool).
 */
final class DisposalProcessor
{
    public static function process(
        DisposeOfSharePoolingToken $disposal,
        SharePoolingTransactions $transactions,
        ?int $position,
    ): SharePoolingTokenDisposal {
        $sameDayQuantityBreakdown = new QuantityBreakdown();
        $thirtyDayQuantityBreakdown = new QuantityBreakdown();

        $costBasis = self::calculateCostBasis(
            transactions: $transactions,
            date: $disposal->date,
            sameDayQuantityBreakdown: $sameDayQuantityBreakdown,
            thirtyDayQuantityBreakdown: $thirtyDayQuantityBreakdown,
            remainingQuantity: $disposal->quantity,
        );

        // Disposals being replayed already have a position, in which case we restore
        // that position to make sure the disposal is inserted back where it should
        return (new SharePoolingTokenDisposal(
            date: $disposal->date,
            quantity: $disposal->quantity,
            costBasis: $costBasis,
            proceeds: $disposal->proceeds,
            sameDayQuantityBreakdown: $sameDayQuantityBreakdown,
            thirtyDayQuantityBreakdown: $thirtyDayQuantityBreakdown,
        ))->setPosition($position);
    }

    private static function calculateCostBasis(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        QuantityBreakdown $sameDayQuantityBreakdown,
        QuantityBreakdown $thirtyDayQuantityBreakdown,
        Quantity $remainingQuantity,
    ): FiatAmount {
        assert($transactions->first() !== null);

        $costBasis = $transactions->first()->costBasis->nilAmount();

        return self::processSameDayAcquisitions(
            $transactions,
            $date,
            $sameDayQuantityBreakdown,
            $thirtyDayQuantityBreakdown,
            $remainingQuantity,
            $costBasis,
        );
    }

    private static function processSameDayAcquisitions(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        QuantityBreakdown $sameDayQuantityBreakdown,
        QuantityBreakdown $thirtyDayQuantityBreakdown,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): FiatAmount {
        if ($remainingQuantity->isZero()) {
            return $costBasis;
        }

        // Get same-day acquisitions with some quantity not matched with same-day disposals yet
        $sameDayAcquisitions = $transactions->acquisitionsMadeOn($date)->withAvailableSameDayQuantity();

        if ($sameDayAcquisitions->isEmpty()) {
            return self::processAcquisitionsWithinThirtyDays(
                $transactions,
                $date,
                $thirtyDayQuantityBreakdown,
                $remainingQuantity,
                $costBasis,
            );
        }

        // Get the same-day average cost basis per unit, of all acquisitions of that day (not
        // just the ones with available same-day quantity). We want the absolute value here,
        // regardless of other types of matching, because same-day matching gets priority
        $sameDayAcquisitionsAverageCostBasisPerUnit = $transactions->acquisitionsMadeOn($date)->averageCostBasisPerUnit();

        assert($sameDayAcquisitionsAverageCostBasisPerUnit !== null);

        // Apply this average cost basis to the disposed of asset, up to the
        // quantity acquired that day not yet matched with same-day disposals
        $availableSameDayQuantity = Quantity::minimum($sameDayAcquisitions->availableSameDayQuantity(), $remainingQuantity);
        $costBasis = $costBasis->plus($sameDayAcquisitionsAverageCostBasisPerUnit->multipliedBy($availableSameDayQuantity));

        // Deduct the applied quantity from the same-day acquisitions
        $remainder = $availableSameDayQuantity;
        foreach ($sameDayAcquisitions as $acquisition) {
            $quantityToAssign = Quantity::minimum($remainder, $acquisition->availableSameDayQuantity());
            $sameDayQuantityBreakdown->assignQuantity($quantityToAssign, $acquisition);
            $acquisition->increaseSameDayQuantity($remainder);
            $remainder =  $remainder->minus($quantityToAssign);
            if ($remainder->isZero()) {
                break;
            }
        }

        $remainingQuantity = $remainingQuantity->minus($availableSameDayQuantity);

        return self::processAcquisitionsWithinThirtyDays(
            $transactions,
            $date,
            $thirtyDayQuantityBreakdown,
            $remainingQuantity,
            $costBasis,
        );
    }

    private static function processAcquisitionsWithinThirtyDays(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        QuantityBreakdown $thirtyDayQuantityBreakdown,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): FiatAmount {
        if ($remainingQuantity->isZero()) {
            return $costBasis;
        }

        // Get acquisitions with available 30-day quantity, made within 30 days
        $withinThirtyDaysAcquisitions = $transactions->acquisitionsMadeBetween($date->plusDays(1), $date->plusDays(30))
            ->withAvailableThirtyDayQuantity();

        foreach ($withinThirtyDaysAcquisitions as $acquisition) {
            // Apply the acquisition's cost basis to the disposed of asset up to the remaining quantity
            $thirtyDayQuantityToApply = Quantity::minimum($acquisition->availableThirtyDayQuantity(), $remainingQuantity);

            // Also deduct same-day disposals with available same-day quantity that haven't been processed yet
            $sameDayDisposals = $transactions->disposalsMadeOn($acquisition->date)
                ->unprocessed()
                ->withAvailableSameDayQuantity();

            foreach ($sameDayDisposals as $disposal) {
                $sameDayQuantityToApply = Quantity::minimum($disposal->availableSameDayQuantity(), $thirtyDayQuantityToApply);
                $disposal->sameDayQuantityBreakdown->assignQuantity($sameDayQuantityToApply, $acquisition);
                $acquisition->increaseSameDayQuantity($sameDayQuantityToApply);
                $thirtyDayQuantityToApply = $thirtyDayQuantityToApply->minus($sameDayQuantityToApply);
                if ($thirtyDayQuantityToApply->isZero()) {
                    break;
                }
            }

            if ($thirtyDayQuantityToApply->isZero()) {
                continue;
            }

            $averageCostBasisPerUnit = $acquisition->averageCostBasisPerUnit();

            $costBasis = $averageCostBasisPerUnit
                ? $costBasis->plus($averageCostBasisPerUnit->multipliedBy($thirtyDayQuantityToApply))
                : $costBasis;

            $thirtyDayQuantityBreakdown->assignQuantity($thirtyDayQuantityToApply, $acquisition);
            $acquisition->increaseThirtyDayQuantity($thirtyDayQuantityToApply);
            $remainingQuantity = $remainingQuantity->minus($thirtyDayQuantityToApply);

            // Continue until there are no more transactions or we've covered all disposed tokens
            if ($remainingQuantity->isZero()) {
                break;
            }
        }

        return self::processSection104PoolAcquisitions($transactions, $date, $remainingQuantity, $costBasis);
    }

    private static function processSection104PoolAcquisitions(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $remainingQuantity,
        FiatAmount $costBasis,
    ): FiatAmount {
        if ($remainingQuantity->isZero()) {
            return $costBasis;
        }

        // Get acquisitions made before or on the day of the disposal
        if (($priorAcquisitions = $transactions->acquisitionsMadeBeforeOrOn($date))->isEmpty()) {
            return $costBasis;
        }

        // Get the average cost basis per unit for the quantity that went to the section 104 pool
        $averageCostBasisPerUnit = $priorAcquisitions->averageSection104PoolCostBasisPerUnit();

        // Apply the section 104 pool's average cost basis per unit to the remainder
        $costBasis = $averageCostBasisPerUnit
            ? $costBasis->plus($averageCostBasisPerUnit->multipliedBy($remainingQuantity))
            : $costBasis;

        return $costBasis;
    }
}
