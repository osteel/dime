<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\ValueObjects\Exceptions\SharePoolingTokenAcquisitionException;
use Domain\Tests\Aggregates\SharePooling\Factories\ValueObjects\SharePoolingTokenAcquisitionFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class SharePoolingTokenAcquisition extends SharePoolingTransaction implements SerializablePayload
{
    private Quantity $sameDayQuantity;
    private Quantity $thirtyDayQuantity;

    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
        ?Quantity $sameDayQuantity = null,
        ?Quantity $thirtyDayQuantity = null,
    ) {
        $this->sameDayQuantity = $sameDayQuantity ?? Quantity::zero();
        $this->thirtyDayQuantity = $thirtyDayQuantity ?? Quantity::zero();
    }

    /** @return SharePoolingTokenAcquisitionFactory<static> */
    protected static function newFactory(): SharePoolingTokenAcquisitionFactory
    {
        return SharePoolingTokenAcquisitionFactory::new();
    }

    public function copy(): static
    {
        return (new self(
            $this->date,
            $this->quantity,
            $this->costBasis,
            $this->sameDayQuantity,
            $this->thirtyDayQuantity,
        ))->setPosition($this->position);
    }

    public function sameDayQuantity(): Quantity
    {
        return $this->sameDayQuantity;
    }

    public function thirtyDayQuantity(): Quantity
    {
        return $this->thirtyDayQuantity;
    }

    public function section104PoolCostBasis(): FiatAmount
    {
        return $this->costBasis->dividedBy($this->quantity)->multipliedBy($this->section104PoolQuantity());
    }

    /** Increase the same-day quantity and adjust the 30-day quantity accordingly. */
    public function increaseSameDayQuantity(Quantity $quantity): self
    {
        //print_r('INCREASE SAME-DAY QUANTITY: '.$quantity->__toString().' (Position: '.$this->position.' | ID: '.spl_object_id($this).')' . "\n");
        // Adjust same-day quantity
        $quantityToAdd = Quantity::minimum($quantity, $this->availableSameDayQuantity());
        $this->sameDayQuantity = $this->sameDayQuantity->plus($quantityToAdd);
        //print_r($this->sameDayQuantity);

        // Adjust 30-day quantity
        $quantityToDeduct = Quantity::minimum($quantityToAdd, $this->thirtyDayQuantity);
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus($quantityToDeduct);

        return $this;
    }

    /** @throws SharePoolingTokenAcquisitionException */
    public function decreaseSameDayQuantity(Quantity $quantity): self
    {
        if ($quantity->isGreaterThan($this->sameDayQuantity)) {
            throw SharePoolingTokenAcquisitionException::insufficientSameDayQuantity($quantity, $this->sameDayQuantity);
        }

        $this->sameDayQuantity = $this->sameDayQuantity->minus(($quantity));

        return $this;
    }

    public function increaseThirtyDayQuantity(Quantity $quantity): self
    {
        $quantityToAdd = Quantity::minimum($quantity, $this->availableThirtyDayQuantity());
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->plus($quantityToAdd);

        return $this;
    }

    /** @throws SharePoolingTokenAcquisitionException */
    public function decreaseThirtyDayQuantity(Quantity $quantity): self
    {
        if ($quantity->isGreaterThan($this->thirtyDayQuantity)) {
            throw SharePoolingTokenAcquisitionException::insufficientThirtyDayQuantity($quantity, $this->thirtyDayQuantity);
        }

        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus(($quantity));

        return $this;
    }

    /** @return array<string, string|int|null|array<string, string>> */
    public function toPayload(): array
    {
        return [
            'date' => $this->date->__toString(),
            'quantity' => $this->quantity->__toString(),
            'cost_basis' => $this->costBasis->toPayload(),
            'same_day_quantity' => $this->sameDayQuantity->__toString(),
            'thirty_day_quantity' => $this->thirtyDayQuantity->__toString(),
            'position' => $this->position,
        ];
    }

    /** @param array<string, string|array<string, string>> $payload */
    public static function fromPayload(array $payload): static
    {
        return (new static(
            LocalDate::parse($payload['date']), // @phpstan-ignore-line
            new Quantity($payload['quantity']), // @phpstan-ignore-line
            FiatAmount::fromPayload($payload['cost_basis']), // @phpstan-ignore-line
            new Quantity($payload['same_day_quantity']), // @phpstan-ignore-line
            new Quantity($payload['thirty_day_quantity']), // @phpstan-ignore-line
        ))->setPosition((int) $payload['position']);
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
