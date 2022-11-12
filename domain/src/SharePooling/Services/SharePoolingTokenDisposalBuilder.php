<?php

namespace Domain\SharePooling\Services;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final class SharePoolingTokenDisposalBuilder
{
    public static function make(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        Quantity $quantity,
        FiatAmount $disposalProceeds,
        ?int $position,
    ): SharePoolingTokenDisposal {
        $sameDayQuantityBreakdown = new QuantityBreakdown();
        $thirtyDayQuantityBreakdown = new QuantityBreakdown();

        $costBasis = self::calculateCostBasis(
            $transactions,
            $date,
            $sameDayQuantityBreakdown,
            $thirtyDayQuantityBreakdown,
            $quantity,
        );

        $disposal = new SharePoolingTokenDisposal(
            date: $date,
            quantity: $quantity,
            costBasis: $costBasis,
            disposalProceeds: $disposalProceeds,
            sameDayQuantityBreakdown: $sameDayQuantityBreakdown,
            thirtyDayQuantityBreakdown: $thirtyDayQuantityBreakdown,
        );

        // Disposals being replayed already have a position, in which case we restore
        // that position to make sure the disposal is inserted back where it should
        return is_null($position) ? $disposal : $disposal->setPosition($position);
    }

    private static function calculateCostBasis(
        SharePoolingTransactions $transactions,
        LocalDate $date,
        QuantityBreakdown $sameDayQuantityBreakdown,
        QuantityBreakdown $thirtyDayQuantityBreakdown,
        Quantity $remainingQuantity,
    ): FiatAmount {
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

        // Get the same-day average cost basis per unit. We want the absolute value here,
        // regardless of other types of matching, because same-day matching gets priority
        $sameDayAcquisitionsAverageCostBasisPerUnit = $sameDayAcquisitions->averageCostBasisPerUnit();

        // Apply this average cost basis to the disposed of asset, up to the
        // quantity acquired that day not yet matched with same-day disposals
        $availableSameDayQuantity = Quantity::minimum($sameDayAcquisitions->availableSameDayQuantity(), $remainingQuantity);
        $costBasis = $costBasis->plus($sameDayAcquisitionsAverageCostBasisPerUnit->multipliedBy($availableSameDayQuantity));

        // Deduct the applied quantity from the same-day acquisitions
        $remainder = $availableSameDayQuantity;
        foreach ($sameDayAcquisitions as $acquisition) {
            $sameDayQuantityBreakdown->assignQuantity($remainder, $acquisition);
            $remainder = $acquisition->increaseSameDayQuantity($remainder);
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

        // Get acquisitions with available 30-day quantity, made within 30 days. We work on
        // a copy of those because we don't want the changes to be applied to the aggregate
        // @TODO if it turns out we're not updating the acquisitions
        // after all in the below, no need to work on a copy anymore
        $withinThirtyDaysAcquisitions = $transactions->acquisitionsMadeBetween($date->plusDays(1), $date->plusDays(30))
            ->withAvailableThirtyDayQuantity();
        //->copy();

        foreach ($withinThirtyDaysAcquisitions as $acquisition) {
            // Apply the acquisition's cost basis to the disposed of asset up to the remaining quantity
            $thirtyDayQuantityToApply = Quantity::minimum($acquisition->availableThirtyDayQuantity(), $remainingQuantity);

            // Also deduct same-day disposals with available same-day quantity that haven't been
            // processed yet. It's OK not to work on copies here, as these will soon be replaced
            // and we actually want to keep track of their share of same-day quantity until then
            $sameDayDisposals = $transactions->disposalsMadeOn($acquisition->date)
                ->unprocessed()
                ->withAvailableSameDayQuantity();

            foreach ($sameDayDisposals as $disposal) {
                $sameDayQuantityToApply = Quantity::minimum($disposal->availableSameDayQuantity(), $thirtyDayQuantityToApply);
                $disposal->sameDayQuantityBreakdown->assignQuantity($sameDayQuantityToApply, $acquisition);
                // @TODO While not incorrect, not sure we actually care about this update
                $acquisition->increaseSameDayQuantity($sameDayQuantityToApply);
                $thirtyDayQuantityToApply = $thirtyDayQuantityToApply->minus($sameDayQuantityToApply);
                if ($thirtyDayQuantityToApply->isZero()) {
                    break;
                }
            }

            if ($thirtyDayQuantityToApply->isZero()) {
                continue;
            }

            $costBasis = $costBasis->plus($acquisition->averageCostBasisPerUnit()->multipliedBy($thirtyDayQuantityToApply));
            $thirtyDayQuantityBreakdown->assignQuantity($thirtyDayQuantityToApply, $acquisition);
            // @TODO While not incorrect, not sure we actually care about this update
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
        $costBasis = $costBasis->plus($averageCostBasisPerUnit->multipliedBy($remainingQuantity));

        return $costBasis;
    }
}
