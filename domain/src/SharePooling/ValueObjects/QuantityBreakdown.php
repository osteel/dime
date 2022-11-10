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

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    /** @throws QuantityBreakdownException */
    public function assignQuantity(Quantity $quantity, SharePoolingTransaction $transaction): self
    {
        if (! $transaction->processed || is_null($transaction->getPosition())) {
            throw QuantityBreakdownException::unassignableTransaction($transaction);
        }

        $assigned = ($this->breakdown[$transaction->getPosition()] ?? Quantity::zero())->plus($quantity);

        $this->breakdown[$transaction->getPosition()] = $assigned;
        $this->quantity = $this->quantity->plus($quantity);

        return $this;
    }

    public function copy(): QuantityBreakdown
    {
        return new self($this->breakdown);
    }
}
