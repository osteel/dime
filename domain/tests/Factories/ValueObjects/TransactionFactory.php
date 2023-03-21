<?php

namespace Domain\Tests\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Enums\Operation;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transaction;
use Tests\Factories\PlainObjectFactory;

/**
 * @template TModel of Transaction
 *
 * @extends PlainObjectFactory
 */
class TransactionFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = Transaction::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'operation' => Operation::Receive,
            'marketValue' => FiatAmount::GBP('100'),
            'isIncome' => false,
            'sentAsset' => null,
            'sentQuantity' => Quantity::zero(),
            'sentAssetIsNft' => false,
            'receivedAsset' => 'BTC',
            'receivedQuantity' => new Quantity('1'),
            'receivedAssetIsNft' => false,
            'feeCurrency' => null,
            'feeQuantity' => Quantity::zero(),
            'feeMarketValue' => null,
        ];
    }

    public function receive(): static
    {
        return $this->state([
            'operation' => Operation::Receive,
            'sentAsset' => null,
            'sentQuantity' => Quantity::zero(),
            'receivedAsset' => 'BTC',
            'receivedQuantity' => new Quantity('1'),
        ]);
    }

    public function income(): static
    {
        return $this->receive()->state([
            'isIncome' => true,
        ]);
    }

    public function receiveNft(): static
    {
        return $this->receive()->state([
            'receivedAsset' => md5(time() . 'receive'),
            'receivedAssetIsNft' => true,
            'receivedQuantity' => new Quantity('1'),
        ]);
    }

    public function send(): static
    {
        return $this->state([
            'operation' => Operation::Send,
            'sentAsset' => 'BTC',
            'sentQuantity' => new Quantity('1'),
            'receivedAsset' => null,
            'receivedQuantity' => Quantity::zero(),
        ]);
    }

    public function sendNft(): static
    {
        return $this->send()->state([
            'sentAsset' => md5(time() . 'send'),
            'sentAssetIsNft' => true,
            'sentQuantity' => new Quantity('1'),
        ]);
    }

    public function swap(): static
    {
        return $this->state([
            'operation' => Operation::Swap,
            'sentAsset' => 'BTC',
            'sentQuantity' => new Quantity('1'),
            'receivedAsset' => 'ETH',
            'receivedQuantity' => new Quantity('10'),
        ]);
    }

    public function swapToNft(): static
    {
        return $this->swap()->state([
            'receivedAsset' => md5(time() . 'receive'),
            'receivedAssetIsNft' => true,
            'receivedQuantity' => new Quantity('1'),
        ]);
    }

    public function swapFromNft(): static
    {
        return $this->swap()->state([
            'sentAsset' => md5(time() . 'send'),
            'sentAssetIsNft' => true,
            'sentQuantity' => new Quantity('1'),
        ]);
    }

    public function swapToFiat(): static
    {
        return $this->swap()->state([
            'receivedAsset' => FiatCurrency::GBP->value,
            'receivedQuantity' => new Quantity('1000'),
        ]);
    }

    public function swapFromFiat(): static
    {
        return $this->swap()->state([
            'sentAsset' => FiatCurrency::GBP->value,
            'sentQuantity' => new Quantity('1000'),
        ]);
    }

    public function swapNfts(): static
    {
        return $this->swapToNft()->swapFromNft();
    }

    public function transfer(): static
    {
        return $this->state([
            'operation' => Operation::Transfer,
            'marketValue' => null,
            'sentAsset' => 'BTC',
            'sentQuantity' => new Quantity('1'),
            'receivedAsset' => null,
            'receivedQuantity' => Quantity::zero(),
        ]);
    }

    public function transferNft(): static
    {
        return $this->transfer()->state([
            'sentAsset' => md5(time() . 'send'),
            'sentAssetIsNft' => true,
            'sentQuantity' => new Quantity('1'),
        ]);
    }

    public function withFee(?FiatAmount $marketValue = null): static
    {
        return $this->state([
            'feeCurrency' => 'BTC',
            'feeQuantity' => new Quantity('0.0001'),
            'feeMarketValue' => $marketValue ?? FiatAmount::GBP('10'),
        ]);
    }

    public function withFeeInFiat(?FiatAmount $marketValue = null): static
    {
        return $this->state([
            'feeCurrency' => $marketValue?->currency->value ?? FiatCurrency::GBP->value,
            'feeQuantity' => $marketValue?->quantity ?? new Quantity('10'),
            'feeMarketValue' => $marketValue ?? FiatAmount::GBP('10'),
        ]);
    }
}
