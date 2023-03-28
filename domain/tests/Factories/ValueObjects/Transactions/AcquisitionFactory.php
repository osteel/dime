<?php

namespace Domain\Tests\Factories\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Acquisition;

/** @extends TransactionFactory<Acquisition> */
class AcquisitionFactory extends TransactionFactory
{
    /** @var string */
    protected $model = Acquisition::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'asset' => new Asset(symbol: 'BTC', isNonFungibleAsset: false),
            'quantity' => new Quantity('1'),
            'marketValue' => FiatAmount::GBP('100'),
            'fee' => null,
            'isIncome' => false,
        ];
    }

    public function income(): static
    {
        return $this->state([
            'isIncome' => true,
        ]);
    }

    public function nonFungibleAsset(): static
    {
        return $this->state([
            'asset' => new Asset(symbol: md5(time()), isNonFungibleAsset: true),
            'quantity' => new Quantity('1'),
        ]);
    }
}
