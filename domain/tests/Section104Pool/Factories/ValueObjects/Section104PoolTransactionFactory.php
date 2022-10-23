<?php

namespace Domain\Tests\Section104Pool\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Section104Pool\Enums\Section104PoolTransactionType;
use Domain\Section104Pool\ValueObjects\Section104PoolTransaction;
use Domain\ValueObjects\FiatAmount;
use Tests\Factories\PlainObjectFactory;

class Section104PoolTransactionFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = Section104PoolTransaction::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'type' => Section104PoolTransactionType::Acquisition,
            'quantity' => '100',
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
        ];
    }
}
