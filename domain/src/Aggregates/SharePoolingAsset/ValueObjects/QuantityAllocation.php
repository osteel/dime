<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects;

use Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions\QuantityAllocationException;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class QuantityAllocation implements SerializablePayload
{
    private Quantity $quantity;

    /** @param array<int,Quantity> $allocation */
    public function __construct(private array $allocation = [])
    {
        $this->quantity = array_reduce(
            $allocation,
            fn (Quantity $total, Quantity $quantity) => $total->plus($quantity),
            Quantity::zero(),
        );
    }

    public function copy(): QuantityAllocation
    {
        return new self($this->allocation);
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    /** @throws QuantityAllocationException */
    public function hasQuantityAllocatedTo(SharePoolingAssetAcquisition $acquisition): bool
    {
        return $this->quantityAllocatedTo($acquisition)->isGreaterThan('0');
    }

    /** @throws QuantityAllocationException */
    public function quantityAllocatedTo(SharePoolingAssetAcquisition $acquisition): Quantity
    {
        if (is_null($acquisition->getPosition())) {
            throw QuantityAllocationException::unprocessedTransaction($acquisition);
        }

        return $this->allocation[$acquisition->getPosition()] ?? Quantity::zero();
    }

    /** @throws QuantityAllocationException */
    public function allocateQuantity(Quantity $quantity, SharePoolingAssetAcquisition $acquisition): self
    {
        if (! $acquisition->isProcessed() || is_null($acquisition->getPosition())) {
            throw QuantityAllocationException::unprocessedTransaction($acquisition);
        }

        $allocated = ($this->allocation[$acquisition->getPosition()] ?? Quantity::zero())->plus($quantity);

        $this->allocation[$acquisition->getPosition()] = $allocated;
        $this->quantity = $this->quantity->plus($quantity);

        return $this;
    }

    /** @return array<int> */
    public function positions(): array
    {
        return array_keys($this->allocation);
    }

    /** @return array{allocation:array<int,string>} */
    public function toPayload(): array
    {
        return [
            'allocation' => array_map(fn (Quantity $quantity) => $quantity->__toString(), $this->allocation),
        ];
    }

    /** @param array{allocation:array<int,string>} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            allocation: array_map(fn (string $quantity) => new Quantity($quantity), $payload['allocation']),
        );
    }
}
