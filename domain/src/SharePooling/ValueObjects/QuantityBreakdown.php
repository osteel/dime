<?php

namespace Domain\SharePooling\ValueObjects;

use Domain\SharePooling\ValueObjects\Exceptions\QuantityBreakdownException;
use Domain\ValueObjects\Quantity;

final class QuantityBreakdown
{
    private Quantity $quantity;

    /** @param array<int, Quantity> $breakdown */
    public function __construct(private array $breakdown = [])
    {
        $this->quantity = array_reduce(
            $breakdown,
            fn (Quantity $total, Quantity $quantity) => $total->plus($quantity),
            Quantity::zero(),
        );
    }

    public function copy(): QuantityBreakdown
    {
        return new self($this->breakdown);
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    /** @throws QuantityBreakdownException */
    public function quantityFor(SharePoolingTokenAcquisition $transaction): Quantity
    {
        if (is_null($transaction->getPosition())) {
            throw QuantityBreakdownException::unassignableTransaction($transaction);
        }

        return $this->breakdown[$transaction->getPosition()] ?? Quantity::zero();
    }

    /** @throws QuantityBreakdownException */
    public function assignQuantity(Quantity $quantity, SharePoolingTokenAcquisition $transaction): self
    {
        if (! $transaction->isProcessed() || is_null($transaction->getPosition())) {
            throw QuantityBreakdownException::unassignableTransaction($transaction);
        }

        $assigned = ($this->breakdown[$transaction->getPosition()] ?? Quantity::zero())->plus($quantity);

        $this->breakdown[$transaction->getPosition()] = $assigned;
        $this->quantity = $this->quantity->plus($quantity);

        return $this;
    }

    public function positions(): array
    {
        return array_keys($this->breakdown);
    }
}
