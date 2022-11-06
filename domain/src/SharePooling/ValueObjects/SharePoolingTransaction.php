<?php

namespace Domain\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\Exceptions\SharePoolingTransactionException;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

abstract class SharePoolingTransaction implements Stringable
{
    use HasFactory;

    protected ?int $position = null;

    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
    ) {
    }

    public function setPosition(int $position): static
    {
        if (! is_null($this->position)) {
            throw SharePoolingTransactionException::positionAlreadySet($this, $position);
        }

        $this->position = $position;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function averageCostBasisPerUnit(): ?FiatAmount
    {
        return $this->costBasis->dividedBy($this->quantity);
    }

    public function isReverted(): bool
    {
        return $this instanceof SharePoolingTokenDisposal ? $this->reverted : false;
    }
}
