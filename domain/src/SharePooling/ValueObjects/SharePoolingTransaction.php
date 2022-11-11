<?php

namespace Domain\SharePooling\ValueObjects;

use Domain\SharePooling\ValueObjects\Exceptions\SharePoolingTransactionException;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

abstract class SharePoolingTransaction implements Stringable
{
    use HasFactory;

    protected bool $processed;
    protected ?int $position = null;

    abstract public function copy(): static;

    public function setPosition(?int $position = null): static
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

    public function isProcessed(): bool
    {
        return $this instanceof SharePoolingTokenAcquisition ? true : $this->processed;
    }

    public function averageCostBasisPerUnit(): ?FiatAmount
    {
        return $this->costBasis->dividedBy($this->quantity);
    }
}
