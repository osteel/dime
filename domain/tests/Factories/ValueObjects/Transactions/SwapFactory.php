<?php

namespace Domain\Tests\Factories\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Swap;

/** @extends TransactionFactory<Swap> */
class SwapFactory extends TransactionFactory
{
    /** @var string */
    protected $model = Swap::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'disposedOfAsset' => new Asset(symbol: 'BTC', isNonFungibleAsset: false),
            'disposedOfQuantity' => new Quantity('1'),
            'acquiredAsset' => new Asset(symbol: 'ETH', isNonFungibleAsset: false),
            'acquiredQuantity' => new Quantity('10'),
            'marketValue' => FiatAmount::GBP('100'),
            'fee' => null,
        ];
    }

    public function toNonFungibleAsset(): static
    {
        return $this->state([
            'acquiredAsset' => new Asset(symbol: md5(time()), isNonFungibleAsset: true),
            'acquiredQuantity' => new Quantity('1'),
        ]);
    }

    public function fromNonFungibleAsset(): static
    {
        return $this->state([
            'disposedOfAsset' => new Asset(symbol: md5(time()), isNonFungibleAsset: true),
            'disposedOfQuantity' => new Quantity('1'),
        ]);
    }

    public function nonFungibleAssets(): static
    {
        return $this->fromNonFungibleAsset()->toNonFungibleAsset();
    }

    public function toFiat(): static
    {
        return $this->state([
            'acquiredAsset' => new Asset(symbol: FiatCurrency::GBP->value, isNonFungibleAsset: false),
            'acquiredQuantity' => new Quantity('1000'),
        ]);
    }

    public function fromFiat(): static
    {
        return $this->state([
            'disposedOfAsset' => new Asset(symbol: FiatCurrency::GBP->value, isNonFungibleAsset: false),
            'disposedOfQuantity' => new Quantity('1000'),
        ]);
    }
}
