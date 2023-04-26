<?php

namespace Domain\Tests\Factories\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Disposal;

/** @extends TransactionFactory<Disposal> */
class DisposalFactory extends TransactionFactory
{
    /** @var string */
    protected $model = Disposal::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'asset' => new Asset(symbol: 'BTC', isNonFungible: false),
            'quantity' => new Quantity('1'),
            'marketValue' => FiatAmount::GBP('100'),
            'fee' => null,
        ];
    }

    public function nonFungibleAsset(): static
    {
        return $this->state([
            'asset' => new Asset(symbol: md5(time()), isNonFungible: true),
            'quantity' => new Quantity('1'),
        ]);
    }
}
