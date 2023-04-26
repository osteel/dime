<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\Tests\Factories\ValueObjects\Transactions\DisposalFactory;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Exceptions\DisposalException;

final readonly class Disposal extends Transaction
{
    /** @throws DisposalException */
    public function __construct(
        public LocalDate $date,
        public Asset $asset,
        public Quantity $quantity,
        public FiatAmount $marketValue,
        public ?Fee $fee = null,
    ) {
        ! $asset->isFiat() || throw DisposalException::isFiat($this);
    }

    protected static function newFactory(): DisposalFactory
    {
        return DisposalFactory::new();
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
            '%s | disposed of: %s | non-fungible asset: %s | quantity: %s | cost basis: %s | Fee: %s',
            (string) $this->date,
            (string) $this->asset,
            $this->asset->isNonFungible ? 'yes' : 'no',
            (string) $this->quantity,
            (string) $this->marketValue ?: 'N/A',
            (string) $this->fee ?: 'N/A',
        );
    }
}
