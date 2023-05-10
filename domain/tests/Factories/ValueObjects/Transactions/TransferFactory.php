<?php

namespace Domain\Tests\Factories\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Transfer;

/** @extends TransactionFactory<Transfer> */
class TransferFactory extends TransactionFactory
{
    /** @var string */
    protected $model = Transfer::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'asset' => new Asset(symbol: 'BTC', isNonFungible: false),
            'quantity' => new Quantity('1'),
            'fee' => null,
        ];
    }

    public function nonFungibleAsset(): static
    {
        return $this->state([
            'asset' => Asset::nonFungible(md5(time())),
            'quantity' => new Quantity('1'),
        ]);
    }
}
