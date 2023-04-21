<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects;

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class QuantityAllocation implements SerializablePayload
{
    /** @param array<string,Quantity> $allocation */
    public function __construct(private array $allocation = [])
    {
    }

    public function copy(): QuantityAllocation
    {
        return new self($this->allocation);
    }

    public function quantity(): Quantity
    {
        return array_reduce(
            $this->allocation,
            fn (Quantity $total, Quantity $quantity) => $total->plus($quantity),
            Quantity::zero(),
        );
    }

    public function hasQuantityAllocatedTo(SharePoolingAssetAcquisition $acquisition): bool
    {
        return $this->quantityAllocatedTo($acquisition)->isGreaterThan('0');
    }

    public function quantityAllocatedTo(SharePoolingAssetAcquisition $acquisition): Quantity
    {
        return $this->allocation[(string) $acquisition->id] ?? Quantity::zero();
    }

    public function allocateQuantity(Quantity $quantity, SharePoolingAssetAcquisition $acquisition): self
    {
        $allocated = ($this->allocation[(string) $acquisition->id] ?? Quantity::zero())->plus($quantity);

        $this->allocation[(string) $acquisition->id] = $allocated;

        return $this;
    }

    /** @return list<SharePoolingAssetTransactionId> */
    public function transactionIds(): array
    {
        return array_map(fn (string $id) => SharePoolingAssetTransactionId::fromString($id), array_keys($this->allocation));
    }

    /** @return array{allocation:array<string,string>} */
    public function toPayload(): array
    {
        return [
            'allocation' => array_map(fn (Quantity $quantity) => (string) $quantity, $this->allocation),
        ];
    }

    /** @param array{allocation:array<string,string>} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            allocation: array_map(fn (string $quantity) => new Quantity($quantity), $payload['allocation']),
        );
    }
}
