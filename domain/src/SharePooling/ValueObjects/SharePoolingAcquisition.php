<?php

namespace Domain\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Services\Math\Math;
use Domain\Tests\SharePooling\Factories\ValueObjects\SharePoolingAcquisitionFactory;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class SharePoolingAcquisition extends SharePoolingTransaction
{
    use HasFactory;

    public function __construct(
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
        public readonly string $section104PoolQuantity,
    ) {
    }

    protected static function newFactory(): SharePoolingAcquisitionFactory
    {
        return SharePoolingAcquisitionFactory::new();
    }

    public function hasSection104PoolQuantity(): bool
    {
        return Math::gt($this->section104PoolQuantity, '0');
    }

    public function section104PoolCostBasis(): FiatAmount
    {
        return $this->costBasis->dividedBy($this->quantity)->multipliedBy($this->section104PoolCostBasis());
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
