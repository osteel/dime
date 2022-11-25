<?php

declare(strict_types=1);

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

    protected bool $processed;
    protected ?int $position = null;

    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
    ) {
    }

    abstract public function copy(): static;

    /** @throws SharePoolingTransactionException */
    public function setPosition(?int $position = null): static
    {
        if (! is_null($this->position)) {
            throw SharePoolingTransactionException::positionAlreadySet($this, $this->position);
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

    public function hasSameDayQuantity(): bool
    {
        return $this->sameDayQuantity()->isGreaterThan('0');
    }

    abstract public function sameDayQuantity(): Quantity;

    public function hasThirtyDayQuantity(): bool
    {
        return $this->thirtyDayQuantity()->isGreaterThan('0');
    }

    abstract public function thirtyDayQuantity(): Quantity;

    public function hasSection104PoolQuantity(): bool
    {
        return $this->section104PoolQuantity()->isGreaterThan('0');
    }

    public function section104PoolQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity()->plus($this->thirtyDayQuantity()));
    }

    public function hasAvailableSameDayQuantity(): bool
    {
        return $this->availableSameDayQuantity()->isGreaterThan('0');
    }

    public function availableSameDayQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity());
    }

    public function hasAvailableThirtyDayQuantity(): bool
    {
        return $this->availableThirtyDayQuantity()->isGreaterThan('0');
    }

    public function availableThirtyDayQuantity(): Quantity
    {
        // Same-day quantity always gets priority, and it is assumed that the existing
        // 30-day quantity has already been matched with priority transactions. That
        // leaves us with the section 104 pool quantity, which is what we return
        return $this->section104PoolQuantity();
    }
}
