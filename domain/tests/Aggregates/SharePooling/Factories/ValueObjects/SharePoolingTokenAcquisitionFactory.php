<?php

namespace Domain\Tests\Aggregates\SharePooling\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Tests\Factories\PlainObjectFactory;

/**
 * @template TModel of SharePoolingTokenAcquisition
 *
 * @extends PlainObjectFactory
 */
class SharePoolingTokenAcquisitionFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = SharePoolingTokenAcquisition::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'quantity' => new Quantity('100'),
            'costBasis' => FiatAmount::GBP('100'),
            'sameDayQuantity' => null,
            'thirtyDayQuantity' => null,
        ];
    }

    public function copyFrom(SharePoolingTokenAcquisition $transaction): static
    {
        return $this->state([
            'date' => $transaction->date,
            'quantity' => $transaction->quantity,
            'costBasis' => $transaction->costBasis,
        ]);
    }
}
