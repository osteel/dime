<?php

namespace Domain\Tests\Aggregates\SharePoolingAsset\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposal;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Tests\Factories\PlainObjectFactory;

/**
 * @template TModel of SharePoolingAssetDisposal
 *
 * @extends PlainObjectFactory
 */
class SharePoolingAssetDisposalFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = SharePoolingAssetDisposal::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'quantity' => new Quantity('100'),
            'costBasis' => FiatAmount::GBP('100'),
            'proceeds' => FiatAmount::GBP('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation(),
            'thirtyDayQuantityAllocation' => new QuantityAllocation(),
            'processed' => true,
        ];
    }

    public function copyFrom(SharePoolingAssetDisposal $transaction): static
    {
        return $this->state([
            'date' => $transaction->date,
            'quantity' => $transaction->quantity,
            'costBasis' => $transaction->costBasis,
            'proceeds' => $transaction->proceeds,
            'sameDayQuantityAllocation' => $transaction->sameDayQuantityAllocation->copy(),
            'thirtyDayQuantityAllocation' => $transaction->thirtyDayQuantityAllocation->copy(),
        ]);
    }

    public function processed(): static
    {
        return $this->state(['processed' => true]);
    }

    public function revert(SharePoolingAssetDisposal $transaction): static
    {
        return $this->copyFrom($transaction)->state([
            'sameDayQuantityAllocation' => new QuantityAllocation(),
            'thirtyDayQuantityAllocation' => new QuantityAllocation(),
        ]);
    }

    public function unprocessed(): static
    {
        return $this->state(['processed' => false]);
    }

    public function withSameDayQuantity(Quantity $quantity, int $position): static
    {
        $sameDayQuantity = $this->getLatest('sameDayQuantityAllocation') ?? new QuantityAllocation();

        return $this->state([
            'sameDayQuantityAllocation' => $sameDayQuantity->allocateQuantity(
                $quantity,
                SharePoolingAssetAcquisition::factory()->make()->setPosition($position),
            ),
        ]);
    }

    public function withThirtyDayQuantity(Quantity $quantity, int $position): static
    {
        $thirtyDayQuantity = $this->getLatest('thirtyDayQuantityAllocation') ?? new QuantityAllocation();

        return $this->state([
            'thirtyDayQuantityAllocation' => $thirtyDayQuantity->allocateQuantity(
                $quantity,
                SharePoolingAssetAcquisition::factory()->make()->setPosition($position),
            ),
        ]);
    }
}
