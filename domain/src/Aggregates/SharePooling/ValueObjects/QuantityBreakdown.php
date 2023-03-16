<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\ValueObjects;

use Domain\Aggregates\SharePooling\ValueObjects\Exceptions\QuantityBreakdownException;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class QuantityBreakdown implements SerializablePayload
{
    private Quantity $quantity;

    /** @param array<int,Quantity> $breakdown */
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
    public function hasQuantityMatchedWith(SharePoolingTokenAcquisition $acquisition): bool
    {
        return $this->quantityMatchedWith($acquisition)->isGreaterThan('0');
    }

    /** @throws QuantityBreakdownException */
    public function quantityMatchedWith(SharePoolingTokenAcquisition $acquisition): Quantity
    {
        if (is_null($acquisition->getPosition())) {
            throw QuantityBreakdownException::unassignableTransaction($acquisition);
        }

        return $this->breakdown[$acquisition->getPosition()] ?? Quantity::zero();
    }

    /** @throws QuantityBreakdownException */
    public function assignQuantity(Quantity $quantity, SharePoolingTokenAcquisition $acquisition): self
    {
        if (! $acquisition->isProcessed() || is_null($acquisition->getPosition())) {
            throw QuantityBreakdownException::unassignableTransaction($acquisition);
        }

        $assigned = ($this->breakdown[$acquisition->getPosition()] ?? Quantity::zero())->plus($quantity);

        $this->breakdown[$acquisition->getPosition()] = $assigned;
        $this->quantity = $this->quantity->plus($quantity);

        return $this;
    }

    /** @return array<int> */
    public function positions(): array
    {
        return array_keys($this->breakdown);
    }

    /** @return array<string,array<string>> */
    public function toPayload(): array
    {
        return [
            'breakdown' => array_map(fn (Quantity $quantity) => $quantity->__toString(), $this->breakdown),
        ];
    }

    /** @param array<string,array<string>> $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            breakdown: array_map(fn (string $quantity) => new Quantity($quantity), $payload['breakdown']),
        );
    }
}
