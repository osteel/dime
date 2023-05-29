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
        LocalDate $date,
        public Asset $asset,
        public Quantity $quantity,
        ?Fee $fee = null,
    ) {
        parent::__construct($date, $fee);
    }

    protected static function newFactory(): TransferFactory
    {
        return TransferFactory::new();
    }

    public function hasNonFungibleAsset(): bool
    {
        return $this->asset->isNonFungible;
    }

    public function hasSharePoolingAsset(): bool
    {
        return ! $this->asset->isNonFungible;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s | transferred: %s | non-fungible asset: %s | quantity: %s | Fee: %s',
            $this->date,
            $this->asset,
            $this->asset->isNonFungible ? 'yes' : 'no',
            $this->quantity,
            $this->fee ?: 'N/A',
        );
    }
}
