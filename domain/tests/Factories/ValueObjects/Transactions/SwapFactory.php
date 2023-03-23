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
            'disposedOfAsset' => new Asset(symbol: 'BTC', isNft: false),
            'disposedOfQuantity' => new Quantity('1'),
            'acquiredAsset' => new Asset(symbol: 'ETH', isNft: false),
            'acquiredQuantity' => new Quantity('10'),
            'marketValue' => FiatAmount::GBP('100'),
            'fee' => null,
        ];
    }

    public function toNft(): static
    {
        return $this->state([
            'acquiredAsset' => new Asset(symbol: md5(time()), isNft: true),
            'acquiredQuantity' => new Quantity('1'),
        ]);
    }

    public function fromNft(): static
    {
        return $this->state([
            'disposedOfAsset' => new Asset(symbol: md5(time()), isNft: true),
            'disposedOfQuantity' => new Quantity('1'),
        ]);
    }

    public function nfts(): static
    {
        return $this->fromNft()->toNft();
    }

    public function toFiat(): static
    {
        return $this->state([
            'acquiredAsset' => new Asset(symbol: FiatCurrency::GBP->value, isNft: false),
            'acquiredQuantity' => new Quantity('1000'),
        ]);
    }

    public function fromFiat(): static
    {
        return $this->state([
            'disposedOfAsset' => new Asset(symbol: FiatCurrency::GBP->value, isNft: false),
            'disposedOfQuantity' => new Quantity('1000'),
        ]);
    }
}
