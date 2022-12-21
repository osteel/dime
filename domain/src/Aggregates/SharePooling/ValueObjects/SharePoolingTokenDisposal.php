<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Tests\Aggregates\SharePooling\Factories\ValueObjects\SharePoolingTokenDisposalFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class SharePoolingTokenDisposal extends SharePoolingTransaction implements SerializablePayload
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $proceeds,
        public readonly QuantityBreakdown $sameDayQuantityBreakdown,
        public readonly QuantityBreakdown $thirtyDayQuantityBreakdown,
        protected bool $processed = true,
    ) {
    }

    /** @return SharePoolingTokenDisposalFactory<static> */
    protected static function newFactory(): SharePoolingTokenDisposalFactory
    {
        return SharePoolingTokenDisposalFactory::new();
    }

    public function copy(): static
    {
        return (new self(
            $this->date,
            $this->quantity,
            $this->costBasis,
            $this->proceeds,
            $this->sameDayQuantityBreakdown->copy(),
            $this->thirtyDayQuantityBreakdown->copy(),
            $this->processed,
        ))->setPosition($this->position);
    }

    /** Return a copy of the disposal with reset quantities and marked as unprocessed. */
    public function copyAsUnprocessed(): SharePoolingTokenDisposal
    {
        return (new SharePoolingTokenDisposal(
            date: $this->date,
            quantity: $this->quantity,
            costBasis: $this->costBasis->nilAmount(),
            proceeds: $this->proceeds,
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
            processed: false,
        ))->setPosition($this->position);
    }

    public function sameDayQuantity(): Quantity
    {
        return $this->sameDayQuantityBreakdown->quantity();
    }

    public function thirtyDayQuantity(): Quantity
    {
        return $this->thirtyDayQuantityBreakdown->quantity();
    }

    /** @throws \Domain\Aggregates\SharePooling\ValueObjects\Exceptions\QuantityBreakdownException */
    public function hasThirtyDayQuantityMatchedWith(SharePoolingTokenAcquisition $acquisition): bool
    {
        return $this->thirtyDayQuantityBreakdown->hasQuantityMatchedWith($acquisition);
    }

    /** @throws \Domain\Aggregates\SharePooling\ValueObjects\Exceptions\QuantityBreakdownException */
    public function thirtyDayQuantityMatchedWith(SharePoolingTokenAcquisition $acquisition): Quantity
    {
        return $this->thirtyDayQuantityBreakdown->quantityMatchedWith($acquisition);
    }

    /** @return array<string, string|int|bool|null|array<string, string|array<string>>> */
    public function toPayload(): array
    {
        return [
            'date' => $this->date->__toString(),
            'quantity' => $this->quantity->__toString(),
            'cost_basis' => $this->costBasis->toPayload(),
            'proceeds' => $this->proceeds->toPayload(),
            'same_day_quantity_breakdown' => $this->sameDayQuantityBreakdown->toPayload(),
            'thirty_day_quantity_breakdown' => $this->thirtyDayQuantityBreakdown->toPayload(),
            'processed' => $this->processed,
            'position' => $this->position,
        ];
    }

    /** @param array<string, string|array<string, string|array<string>>> $payload */
    public static function fromPayload(array $payload): static
    {
        return (new static(
            LocalDate::parse($payload['date']), // @phpstan-ignore-line
            new Quantity($payload['quantity']), // @phpstan-ignore-line
            FiatAmount::fromPayload($payload['cost_basis']), // @phpstan-ignore-line
            FiatAmount::fromPayload($payload['proceeds']), // @phpstan-ignore-line
            QuantityBreakdown::fromPayload($payload['same_day_quantity_breakdown']), // @phpstan-ignore-line
            QuantityBreakdown::fromPayload($payload['thirty_day_quantity_breakdown']), // @phpstan-ignore-line
            (bool) $payload['processed'],
        ))->setPosition((int) $payload['position']);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: disposed of %s tokens for %s (cost basis: %s)',
            $this->date,
            $this->quantity,
            $this->proceeds,
            $this->costBasis,
        );
    }
}
