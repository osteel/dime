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
            'isIncome' => false,
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'sentAsset' => null,
            'sentQuantity' => Quantity::zero(),
            'sentAssetIsNft' => false,
            'receivedAsset' => 'BTC',
            'receivedQuantity' => new Quantity('1'),
            'receivedAssetIsNft' => false,
            'transactionFeeCurrency' => null,
            'transactionFeeQuantity' => Quantity::zero(),
            'exchangeFeeCurrency' => null,
            'exchangeFeeQuantity' => Quantity::zero(),
        ];
    }

    public function income(): static
    {
        return $this->receive()->state([
            'isIncome' => true,
        ]);
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

    public function swap(): static
    {
        return $this->state([
            'operation' => Operation::Send,
            'sentAsset' => 'BTC',
            'sentQuantity' => new Quantity('1'),
            'receivedAsset' => 'ETH',
            'receivedQuantity' => new Quantity('10'),
        ]);
    }

    public function transfer(): static
    {
        return $this->state([
            'operation' => Operation::Send,
            'sentAsset' => 'BTC',
            'sentQuantity' => new Quantity('1'),
            'receivedAsset' => null,
            'receivedQuantity' => Quantity::zero(),
        ]);
    }
}
