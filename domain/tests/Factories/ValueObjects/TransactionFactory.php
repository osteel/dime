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
            'marketValue' => new FiatAmount('100', FiatCurrency::GBP),
            'isIncome' => false,
            'sentAsset' => null,
            'sentQuantity' => Quantity::zero(),
            'sentAssetIsNft' => false,
            'receivedAsset' => 'BTC',
            'receivedQuantity' => new Quantity('1'),
            'receivedAssetIsNft' => false,
            'networkFeeCurrency' => null,
            'networkFeeQuantity' => Quantity::zero(),
            'networkFeeMarketValue' => null,
            'platformFeeCurrency' => null,
            'platformFeeQuantity' => Quantity::zero(),
            'platformFeeMarketValue' => null,
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

    public function swapNfts(): static
    {
        return $this->swapToNft()->swapFromNft();
    }

    public function transfer(): static
    {
        return $this->state([
            'operation' => Operation::Transfer,
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

    public function withNetworkFee(?FiatAmount $marketValue = null): static
    {
        return $this->state([
            'networkFeeCurrency' => 'BTC',
            'networkFeeQuantity' => new Quantity('0.0001'),
            'networkFeeMarketValue' => $marketValue ?? new FiatAmount('10', FiatCurrency::GBP),
        ]);
    }

    public function withNetworkFeeInFiat(?FiatAmount $marketValue = null): static
    {
        return $this->state([
            'networkFeeCurrency' => $marketValue?->currency->value ?? FiatCurrency::GBP->value,
            'networkFeeQuantity' => new Quantity($marketValue?->amount ?? '10'),
            'networkFeeMarketValue' => $marketValue ?? new FiatAmount('10', FiatCurrency::GBP),
        ]);
    }

    public function withPlatformFee(?FiatAmount $marketValue = null): static
    {
        return $this->state([
            'platformFeeCurrency' => 'BTC',
            'platformFeeQuantity' => new Quantity('0.0001'),
            'platformFeeMarketValue' => $marketValue ?? new FiatAmount('10', FiatCurrency::GBP),
        ]);
    }

    public function withPlatformFeeInFiat(?FiatAmount $marketValue = null): static
    {
        return $this->state([
            'platformFeeCurrency' => $marketValue?->currency->value ?? FiatCurrency::GBP->value,
            'platformFeeQuantity' => new Quantity($marketValue?->amount ?? '10'),
            'platformFeeMarketValue' => $marketValue ?? new FiatAmount('10', FiatCurrency::GBP),
        ]);
    }
}
