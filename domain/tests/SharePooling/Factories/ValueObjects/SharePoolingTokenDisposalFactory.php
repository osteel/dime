<?php

namespace Domain\Tests\SharePooling\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Tests\Factories\PlainObjectFactory;

class SharePoolingTokenDisposalFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = SharePoolingTokenDisposal::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'quantity' => new Quantity('100'),
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'disposalProceeds' => new FiatAmount('100', FiatCurrency::GBP),
            'sameDayQuantity' => new Quantity('0'),
            'thirtyDayQuantity' => new Quantity('0'),
            'section104PoolQuantity' => new Quantity('100'),
        ];
    }
}
