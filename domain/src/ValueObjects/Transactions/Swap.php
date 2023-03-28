<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\Tests\Factories\ValueObjects\Transactions\SwapFactory;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Exceptions\SwapException;

final readonly class Swap extends Transaction
{
    /** @throws SwapException */
    public function __construct(
        public LocalDate $date,
        public Asset $disposedOfAsset,
        public Quantity $disposedOfQuantity,
        public Asset $acquiredAsset,
        public Quantity $acquiredQuantity,
        public FiatAmount $marketValue,
        public ?Fee $fee = null,
    ) {
        ! $disposedOfAsset->isFiat() || ! $acquiredAsset->isFiat() || throw SwapException::bothSidesAreFiat($this);
    }

    protected static function newFactory(): SwapFactory
    {
        return SwapFactory::new();
    }

    public function hasNonFungibleAsset(): bool
    {
        return $this->disposedOfAsset->isNonFungibleAsset || $this->acquiredAsset->isNonFungibleAsset;
    }

    public function disposedOfAssetIsSharePoolingAsset(): bool
    {
        return ! $this->disposedOfAsset->isNonFungibleAsset && ! $this->disposedOfAsset->isFiat();
    }

    public function acquiredAssetIsSharePoolingAsset(): bool
    {
        return ! $this->acquiredAsset->isNonFungibleAsset && ! $this->acquiredAsset->isFiat();
    }

    public function hasSharePoolingAsset(): bool
    {
        return $this->disposedOfAssetIsSharePoolingAsset() || $this->acquiredAssetIsSharePoolingAsset();
    }

    public function hasFiat(): bool
    {
        return $this->disposedOfAsset->isFiat() || $this->acquiredAsset->isFiat();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s | disposed of: %s | non-fungible asset: %s | quantity: %s | acquired: %s | non-fungible asset: %s | quantity: %s | cost basis: %s | Fee: %s',
            (string) $this->date,
            (string) $this->disposedOfAsset,
            $this->disposedOfAsset->isNonFungibleAsset ? 'yes' : 'no',
            $this->disposedOfQuantity->__toString(),
            (string) $this->acquiredAsset,
            $this->acquiredAsset->isNonFungibleAsset ? 'yes' : 'no',
            $this->acquiredQuantity->__toString(),
            (string) $this->marketValue ?: 'N/A',
            (string) $this->fee ?: 'N/A',
        );
    }
}
