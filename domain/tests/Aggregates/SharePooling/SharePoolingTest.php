<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\Aggregates\SharePooling\Exceptions\SharePoolingException;
use Domain\Aggregates\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\Enums\FiatCurrency;
use Domain\Tests\Aggregates\SharePooling\SharePoolingTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(SharePoolingTestCase::class);

it('can acquire some share pooling tokens', function () {
    $acquireSharePoolingToken = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $sharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: $acquireSharePoolingToken->date,
            quantity: new Quantity('100'),
            costBasis: $acquireSharePoolingToken->costBasis,
        ),
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireSharePoolingToken)
        ->then($sharePoolingTokenAcquired);
});

it('can acquire more of the same share pooling tokens', function () {
    $someSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::GBP),
    );

    $moreSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('300', FiatCurrency::GBP),
        ))->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokenAcquired)
        ->when($acquireMoreSharePoolingToken)
        ->then($moreSharePoolingTokenAcquired);
});

it('cannot acquire more of the same share pooling tokens because currencies don\'t match', function () {
    $someSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::EUR),
    );

    $cannotAcquireSharePoolingToken = SharePoolingException::cannotAcquireFromDifferentCurrency(
        sharePoolingId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokenAcquired)
        ->when($acquireMoreSharePoolingToken)
        ->expectToFail($cannotAcquireSharePoolingToken);
});

it('can dispose of some share pooling tokens', function () {
    $sharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('200', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('50'),
        proceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $disposeOfSharePoolingToken->date,
            quantity: $disposeOfSharePoolingToken->quantity,
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
            proceeds: $disposeOfSharePoolingToken->proceeds,
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ))->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokenAcquired)
        ->when($disposeOfSharePoolingToken)
        ->then($sharePoolingTokenDisposedOf);
});

it('cannot dispose of some share pooling tokens because currencies don\'t match', function () {
    $sharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('100'),
        proceeds: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotDisposeOfSharePoolingToken = SharePoolingException::cannotDisposeOfFromDifferentCurrency(
        sharePoolingId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokenAcquired)
        ->when($disposeOfSharePoolingToken)
        ->expectToFail($cannotDisposeOfSharePoolingToken);
});

it('cannot dispose of some share pooling tokens because the quantity is too high', function () {
    $sharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('101'),
        proceeds: new FiatAmount('100', FiatCurrency::GBP),
    );

    $cannotDisposeOfSharePoolingToken = SharePoolingException::insufficientQuantity(
        sharePoolingId: $this->aggregateRootId,
        disposalQuantity: $disposeOfSharePoolingToken->quantity,
        availableQuantity: new Quantity('100'),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokenAcquired)
        ->when($disposeOfSharePoolingToken)
        ->expectToFail($cannotDisposeOfSharePoolingToken);
});

it('can dispose of some share pooling tokens on the same day they were acquired', function () {
    $someSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $moreSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('150', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('150'),
        proceeds: new FiatAmount('300', FiatCurrency::GBP),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('100'), position: 1) // $moreSharePoolingTokensAcquired
            ->make([
                'date' => $disposeOfSharePoolingToken->date,
                'quantity' => $disposeOfSharePoolingToken->quantity,
                'costBasis' => new FiatAmount('200', FiatCurrency::GBP),
                'proceeds' => $disposeOfSharePoolingToken->proceeds,
            ])
            ->setPosition(2)
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokensAcquired, $moreSharePoolingTokensAcquired)
        ->when($disposeOfSharePoolingToken)
        ->then($sharePoolingTokenDisposedOf);
});

it('can acquire some share pooling tokens within 30 days of their disposal', function () {
    // Given

    $someSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $someSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('50'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            proceeds: new FiatAmount('75', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    // When

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('25'),
        costBasis: new FiatAmount('20', FiatCurrency::GBP),
    );

    // Then

    $sharePoolingTokenDisposalReverted = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal,
    );

    $moreSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: $acquireMoreSharePoolingToken->date,
            quantity: $acquireMoreSharePoolingToken->quantity,
            costBasis: $acquireMoreSharePoolingToken->costBasis,
            thirtyDayQuantity: new Quantity('25'),
        ))->setPosition(2),
    );

    $correctedSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('25'), position: 2) // $acquireMoreSharePoolingToken
            ->make([
                'costBasis' => new FiatAmount('45', FiatCurrency::GBP),
                'sameDayQuantityBreakdown' => new QuantityBreakdown(),
            ])
            ->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokensAcquired, $someSharePoolingTokensDisposedOf)
        ->when($acquireMoreSharePoolingToken)
        ->then($sharePoolingTokenDisposalReverted, $moreSharePoolingTokenAcquired, $correctedSharePoolingTokensDisposedOf);
});

it('can acquire some share pooling tokens several times within 30 days of their disposal', function () {
    // Given

    $sharePoolingTokenAcquired1 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingTokenDisposedOf1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('25'), position: 1) // $sharePoolingTokenAcquired2
            ->withThirtyDayQuantity(new Quantity('20'), position: 4) // $sharePoolingTokenAcquired3
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => new FiatAmount('115', FiatCurrency::GBP),
            ]),
    );

    $sharePoolingTokenDisposedOf2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('25', FiatCurrency::GBP),
            proceeds: new FiatAmount('50', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $sharePoolingTokenAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('60', FiatCurrency::GBP),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingTokenDisposal1Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf1Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    // When

    $acquireSharePoolingToken4 = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('20'),
        costBasis: new FiatAmount('40', FiatCurrency::GBP),
    );

    // Then

    $sharePoolingTokenDisposal1Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposal2Reverted = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenAcquired4 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: $acquireSharePoolingToken4->date,
            quantity: $acquireSharePoolingToken4->quantity,
            costBasis: $acquireSharePoolingToken4->costBasis,
            thirtyDayQuantity: new Quantity('20'),
        ))->setPosition(5),
    );

    $sharePoolingTokenDisposedOf1Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingTokenAcquired4
            ->make(['costBasis' => new FiatAmount('120', FiatCurrency::GBP)])
            ->setPosition(2),
    );

    $sharePoolingTokenDisposedOf2Corrected = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('15'), position: 5) // $sharePoolingTokenAcquired4
            ->make([
                'costBasis' => new FiatAmount('40', FiatCurrency::GBP),
                'sameDayQuantityBreakdown' => new QuantityBreakdown(),
            ])
            ->setPosition(3),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingTokenAcquired1,
        $sharePoolingTokenAcquired2,
        $sharePoolingTokenDisposedOf1,
        $sharePoolingTokenDisposedOf2,
        $sharePoolingTokenDisposal1Reverted1,
        $sharePoolingTokenAcquired3,
        $sharePoolingTokenDisposedOf1Corrected1,
    )
        ->when($acquireSharePoolingToken4)
        ->then(
            $sharePoolingTokenDisposal1Reverted2,
            $sharePoolingTokenDisposal2Reverted,
            $sharePoolingTokenAcquired4,
            $sharePoolingTokenDisposedOf1Corrected2,
            $sharePoolingTokenDisposedOf2Corrected,
        );
});

it('can dispose of some share pooling tokens on the same day as an acquisition within 30 days of another disposal', function () {
    // Given

    $sharePoolingTokenAcquired1 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingTokenDisposedOf1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('25'), position: 1) // $sharePoolingTokenAcquired2
            ->withThirtyDayQuantity(new Quantity('20'), position: 4) // $sharePoolingTokenAcquired3
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingTokenAcquired4
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => new FiatAmount('120', FiatCurrency::GBP),
            ]),
    );

    $sharePoolingTokenDisposedOf2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withThirtyDayQuantity(new Quantity('15'), position: 5) // $sharePoolingTokenAcquired4
            ->make([
                'date' => LocalDate::parse('2015-10-25'),
                'quantity' => new Quantity('25'),
                'costBasis' => new FiatAmount('40', FiatCurrency::GBP),
            ]),
    );

    $sharePoolingTokenAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('60', FiatCurrency::GBP),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingTokenDisposal1Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf1Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenAcquired4 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-29'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('40', FiatCurrency::GBP),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingTokenDisposal1Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposal2Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf1Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf2Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal,
    );

    // When

    $disposeOfSharePoolingToken3 = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('10'),
        proceeds: new FiatAmount('30', FiatCurrency::GBP),
    );

    // Then

    $sharePoolingTokenDisposal2Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf2Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingTokenAcquired4
            ->make(['costBasis' => new FiatAmount('30', FiatCurrency::GBP)])
            ->setPosition(3),
    );

    $sharePoolingTokenDisposedOf3 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: (
            SharePoolingTokenDisposal::factory()
                ->withSameDayQuantity(new Quantity('10'), position: 5) // $sharePoolingTokenAcquired4
                ->make([
                    'date' => $disposeOfSharePoolingToken3->date,
                    'quantity' => $disposeOfSharePoolingToken3->quantity,
                    'costBasis' => new FiatAmount('20', FiatCurrency::GBP),
                    'proceeds' => $disposeOfSharePoolingToken3->proceeds,
                    'thirtyDayQuantityBreakdown' => new QuantityBreakdown(),
                ])
        )->setPosition(6),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingTokenAcquired1,
        $sharePoolingTokenAcquired2,
        $sharePoolingTokenDisposedOf1,
        $sharePoolingTokenDisposedOf2,
        $sharePoolingTokenDisposal1Reverted1,
        $sharePoolingTokenAcquired3,
        $sharePoolingTokenDisposedOf1Corrected1,
        $sharePoolingTokenDisposal1Reverted2,
        $sharePoolingTokenDisposal2Reverted1,
        $sharePoolingTokenAcquired4,
        $sharePoolingTokenDisposedOf1Corrected2,
        $sharePoolingTokenDisposedOf2Corrected1,
    )
        ->when($disposeOfSharePoolingToken3)
        ->then($sharePoolingTokenDisposal2Reverted2, $sharePoolingTokenDisposedOf2Corrected2, $sharePoolingTokenDisposedOf3);
});

it('can acquire some same-day share pooling tokens several times on the same day as their disposal', function () {
    // Given

    $sharePoolingTokenAcquired1 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            proceeds: new FiatAmount('75', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $sharePoolingTokenDisposalReverted = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('25', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('20'), // $sharePoolingTokenDisposedOf
        ),
    );

    $sharePoolingTokenDisposedOfCorrected = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokenDisposedOf->sharePoolingTokenDisposal)
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingTokenAcquired2
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => new FiatAmount('55', FiatCurrency::GBP),
            ])
            ->setPosition(1),
    );

    // When

    $acquireSharePoolingToken3 = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('10'),
        costBasis: new FiatAmount('14', FiatCurrency::GBP),
    );

    // Then

    $sharePoolingTokenDisposalReverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOfCorrected->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: $acquireSharePoolingToken3->date,
            quantity: $acquireSharePoolingToken3->quantity,
            costBasis: $acquireSharePoolingToken3->costBasis,
            sameDayQuantity: new Quantity('10'), // $sharePoolingTokenDisposedOf
        ))->setPosition(3),
    );

    $sharePoolingTokensDisposedOfCorrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokenDisposedOfCorrected->sharePoolingTokenDisposal)
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingTokenAcquired2
            ->withSameDayQuantity(new Quantity('10'), position: 3) // $sharePoolingTokenAcquired3
            ->make([
                'costBasis' => new FiatAmount('59', FiatCurrency::GBP),
                'thirtyDayQuantityBreakdown' => new QuantityBreakdown(),
            ])
            ->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingTokenAcquired1,
        $sharePoolingTokenDisposedOf,
        $sharePoolingTokenDisposalReverted,
        $sharePoolingTokenAcquired2,
        $sharePoolingTokenDisposedOfCorrected,
    )
        ->when($acquireSharePoolingToken3)
        ->then($sharePoolingTokenDisposalReverted2, $sharePoolingTokenAcquired3, $sharePoolingTokensDisposedOfCorrected2);
});

it('can dispose of some same-day share pooling tokens several times on the same day as several acquisitions', function () {
    // Given

    $sharePoolingTokenAcquired1 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenDisposedOf1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            proceeds: new FiatAmount('75', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $sharePoolingTokenDisposalReverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('25', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('20'), // $sharePoolingTokenDisposedOf1
        ),
    );

    $sharePoolingTokenDisposedOf1Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingTokenAcquired2
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => new FiatAmount('55', FiatCurrency::GBP),
            ])
            ->setPosition(1),
    );

    $sharePoolingTokenDisposalReverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('60'),
            costBasis: new FiatAmount('90', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('30'), // $sharePoolingTokenDisposedOf1
        ),
    );

    $sharePoolingTokenDisposedOf1Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingTokenAcquired2
            ->withSameDayQuantity(new Quantity('30'), position: 3) // $sharePoolingTokenAcquired3
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => new FiatAmount('71.875', FiatCurrency::GBP),
            ])
            ->setPosition(1),
    );

    // When

    $disposeOfSharePoolingToken2 = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('40'),
        proceeds: new FiatAmount('50', FiatCurrency::GBP),
    );

    // Then

    $sharePoolingTokenDisposedOf2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: (
            SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), position: 3) // $sharePoolingTokenAcquired3
            ->make([
                'date' => $disposeOfSharePoolingToken2->date,
                'quantity' => $disposeOfSharePoolingToken2->quantity,
                'costBasis' => new FiatAmount('53.125', FiatCurrency::GBP),
                'proceeds' => $disposeOfSharePoolingToken2->proceeds,
            ])
        )->setPosition(4),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingTokenAcquired1,
        $sharePoolingTokenDisposedOf1,
        $sharePoolingTokenDisposalReverted1,
        $sharePoolingTokenAcquired2,
        $sharePoolingTokenDisposedOf1Corrected1,
        $sharePoolingTokenDisposalReverted2,
        $sharePoolingTokenAcquired3,
        $sharePoolingTokenDisposedOf1Corrected2,
    )
        ->when($disposeOfSharePoolingToken2)
        ->then($sharePoolingTokenDisposedOf2);
});
