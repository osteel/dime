<?php

namespace Domain\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Tests\SharePooling\Factories\ValueObjects\SharePoolingDisposalFactory;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class SharePoolingDisposal extends SharePoolingTransaction
{
    use HasFactory;

    public function __construct(
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $disposalProceeds,
    ) {
    }

    protected static function newFactory(): SharePoolingDisposalFactory
    {
        return SharePoolingDisposalFactory::new();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: disposed of %s tokens for %s (cost basis: %s)',
            $this->date,
            $this->quantity,
            $this->disposalProceeds,
            $this->costBasis,
        );
    }
}
