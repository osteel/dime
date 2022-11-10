<?php

namespace Domain\Tests\SharePooling\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Tests\Factories\PlainObjectFactory;

class SharePoolingTokenDisposalFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = SharePoolingTokenDisposal::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'quantity' => new Quantity('100'),
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'disposalProceeds' => new FiatAmount('100', FiatCurrency::GBP),
            'sameDayQuantity' => new QuantityBreakdown(),
            'thirtyDayQuantity' => new QuantityBreakdown(),
        ];
    }

    private function getLatest(string $attribute): mixed
    {
        $state = $this->states->last(function (callable $state) use ($attribute) {
            return isset($state()[$attribute]);
        });

        return $state ? $state()[$attribute] : null;
    }

    public function copyFrom(SharePoolingTokenDisposal $transaction): static
    {
        return $this->state([
            'date' => $transaction->date,
            'quantity' => $transaction->quantity,
            'costBasis' => $transaction->costBasis,
            'disposalProceeds' => $transaction->disposalProceeds,
            'sameDayQuantity' => $transaction->sameDayQuantity->copy(),
            'thirtyDayQuantity' => $transaction->thirtyDayQuantity->copy(),
        ]);
    }

    public function revert(SharePoolingTokenDisposal $transaction): static
    {
        return $this->copyFrom($transaction)->state([
            'sameDayQuantity' => new QuantityBreakdown(),
            'thirtyDayQuantity' => new QuantityBreakdown(),
        ]);
    }

    public function withSameDayQuantity(Quantity $quantity, SharePoolingTokenAcquisition $transaction, int $position): static
    {
        $sameDayQuantity = $this->getLatest('sameDayQuantity') ?? new QuantityBreakdown();

        return $this->state([
            'sameDayQuantity' => $sameDayQuantity->assignQuantity(
                $quantity,
                SharePoolingTokenAcquisition::factory()->copyFrom($transaction)->make()->setPosition($position),
            ),
        ]);
    }

    public function withThirtyDayQuantity(Quantity $quantity, SharePoolingTokenAcquisition $transaction, int $position): static
    {
        $thirtyDayQuantity = $this->getLatest('thirtyDayQuantity') ?? new QuantityBreakdown();

        return $this->state([
            'thirtyDayQuantity' => $thirtyDayQuantity->assignQuantity(
                $quantity,
                SharePoolingTokenAcquisition::factory()->copyFrom($transaction)->make()->setPosition($position),
            ),
        ]);
    }
}
