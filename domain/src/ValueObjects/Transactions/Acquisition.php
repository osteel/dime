<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\Tests\Factories\ValueObjects\Transactions\AcquisitionFactory;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Exceptions\AcquisitionException;

final readonly class Acquisition extends Transaction
{
    /** @throws AcquisitionException */
    public function __construct(
        public LocalDate $date,
        public Asset $asset,
        public Quantity $quantity,
        public FiatAmount $marketValue,
        public ?Fee $fee = null,
        public bool $isIncome = false,
    ) {
        ! $asset->isFiat() || throw AcquisitionException::isFiat($this);
    }

    protected static function newFactory(): AcquisitionFactory
    {
        return AcquisitionFactory::new();
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
            '%s | acquired: %s | non-fungible asset: %s | quantity: %s | cost basis: %s | income: %s | Fee: %s',
            (string) $this->date,
            (string) $this->asset,
            $this->asset->isNonFungible ? 'yes' : 'no',
            (string) $this->quantity,
            (string) $this->marketValue ?: 'N/A',
            $this->isIncome ? 'yes' : 'no',
            (string) $this->fee ?: 'N/A',
        );
    }
}
