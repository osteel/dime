<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\Tests\Factories\ValueObjects\Transactions\AcquisitionFactory;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final readonly class Acquisition extends Transaction
{
    public function __construct(
        public LocalDate $date,
        public Asset $asset,
        public Quantity $quantity,
        public FiatAmount $marketValue,
        public ?Fee $fee = null,
        public bool $isIncome = false,
    ) {
    }

    protected static function newFactory(): AcquisitionFactory
    {
        return AcquisitionFactory::new();
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
            '%s | acquired: %s | NFT: %s | quantity: %s | cost basis: %s | income: %s | Fee: %s',
            (string) $this->date,
            (string) $this->asset,
            $this->asset->isNft ? 'yes' : 'no',
            (string) $this->quantity,
            (string) $this->marketValue ?: 'N/A',
            $this->isIncome ? 'yes' : 'no',
            (string) $this->fee ?: 'N/A',
        );
    }
}