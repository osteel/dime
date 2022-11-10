<?php

namespace Domain\Tests\SharePooling\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Tests\Factories\PlainObjectFactory;

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
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
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
