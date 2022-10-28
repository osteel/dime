<?php

namespace Domain\Tests\Section104Pool\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Section104Pool\ValueObjects\Section104PoolAcquisition;
use Domain\ValueObjects\FiatAmount;
use Tests\Factories\PlainObjectFactory;

class Section104PoolAcquisitionFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = Section104PoolAcquisition::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'quantity' => '100',
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantityMatchedForPooling' => '0',
        ];
    }
}
