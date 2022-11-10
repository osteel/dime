<?php

namespace Domain\SharePooling\ValueObjects;

use Domain\SharePooling\ValueObjects\Exceptions\SharePoolingTransactionException;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

abstract class SharePoolingTransaction implements Stringable
{
    use HasFactory;

    public readonly bool $processed;
    protected ?int $position = null;

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
}
