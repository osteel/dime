<?php

namespace Domain\Tests\Aggregates\SharePoolingAsset\Factories\Entities;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Tests\Factories\PlainObjectFactory;

/**
 * @template TModel of SharePoolingAssetAcquisition
 *
 * @extends PlainObjectFactory
 */
class SharePoolingAssetAcquisitionFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = SharePoolingAssetAcquisition::class;

    /** @return array */
    public function definition()
    {
        return [
            'id' => SharePoolingAssetTransactionId::generate(),
            'date' => LocalDate::parse('2015-10-21'),
            'quantity' => new Quantity('100'),
            'costBasis' => FiatAmount::GBP('100'),
            'sameDayQuantity' => null,
            'thirtyDayQuantity' => null,
        ];
    }

    public function copyFrom(SharePoolingAssetAcquisition $transaction): static
    {
        return $this->state([
            'date' => $transaction->date,
            'quantity' => $transaction->quantity,
            'costBasis' => $transaction->costBasis,
        ]);
    }
}
