<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\Tests\Factories\ValueObjects\Transactions\TransferFactory;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\Quantity;

final readonly class Transfer extends Transaction
{
    public function __construct(
        public LocalDate $date,
        public Asset $asset,
        public Quantity $quantity,
        public ?Fee $fee = null,
    ) {
    }

    protected static function newFactory(): TransferFactory
    {
        return TransferFactory::new();
    }

    public function hasNft(): bool
    {
        return $this->asset->isNft;
    }

    public function hasSharePoolingAsset(): bool
    {
        return ! $this->asset->isNft;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s | transferred: %s | NFT: %s | quantity: %s | Fee: %s',
            (string) $this->date,
            (string) $this->asset,
            $this->asset->isNft ? 'yes' : 'no',
            (string) $this->quantity,
            (string) $this->fee ?: 'N/A',
        );
    }
}