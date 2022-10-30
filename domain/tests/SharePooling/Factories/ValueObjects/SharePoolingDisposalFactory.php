<?php

namespace Domain\Tests\SharePooling\Factories\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\SharePooling\ValueObjects\SharePoolingDisposal;
use Domain\ValueObjects\FiatAmount;
use Tests\Factories\PlainObjectFactory;

class SharePoolingDisposalFactory extends PlainObjectFactory
{
    /** @var string */
    protected $model = SharePoolingDisposal::class;

    /** @return array */
    public function definition()
    {
        return [
            'date' => LocalDate::parse('2015-10-21'),
            'quantity' => '100',
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'disposalProceeds' => new FiatAmount('100', FiatCurrency::GBP),
        ];
    }
}
