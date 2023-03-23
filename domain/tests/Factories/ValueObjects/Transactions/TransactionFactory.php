<?php

namespace Domain\Tests\Factories\ValueObjects\Transactions;

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Transaction;
use Tests\Factories\PlainObjectFactory;

/**
 * @template TModel of Transaction
 *
 * @extends PlainObjectFactory
 */
abstract class TransactionFactory extends PlainObjectFactory
{
    public function withFee(?FiatAmount $marketValue = null): static
    {
        return $this->state([
            'fee' => new Fee(
                currency: new Asset('BTC'),
                quantity: new Quantity('0.0001'),
                marketValue: $marketValue ?? FiatAmount::GBP('10'),
            ),
        ]);
    }

    public function withFeeInFiat(?FiatAmount $marketValue = null): static
    {
        return $this->state([
            'fee' => new Fee(
                currency: $marketValue?->currency ?? new Asset(FiatCurrency::GBP->value),
                quantity: $marketValue?->quantity ?? new Quantity('10'),
                marketValue: $marketValue ?? FiatAmount::GBP('10'),
            ),
        ]);
    }
}
