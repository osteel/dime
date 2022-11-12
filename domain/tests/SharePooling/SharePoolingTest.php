<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\SharePooling\Exceptions\SharePoolingException;
use Domain\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\Tests\SharePooling\SharePoolingTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(SharePoolingTestCase::class);

beforeEach(function () {
    $this->sharePoolingId = $this->aggregateRootId();
});

it('can acquire some share pooling tokens', function () {
    $acquireSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $sharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::GBP),
    );

    $moreSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::EUR),
    );

    $cannotAcquireSharePoolingToken = SharePoolingException::cannotAcquireFromDifferentFiatCurrency(
        sharePoolingId: $this->sharePoolingId,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('200', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('50'),
        disposalProceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $disposeOfSharePoolingToken->date,
            quantity: $disposeOfSharePoolingToken->quantity,
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
            disposalProceeds: $disposeOfSharePoolingToken->disposalProceeds,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('100'),
        disposalProceeds: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotDisposeOfSharePoolingToken = SharePoolingException::cannotDisposeOfFromDifferentFiatCurrency(
        sharePoolingId: $this->sharePoolingId,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('101'),
        disposalProceeds: new FiatAmount('100', FiatCurrency::GBP),
    );

    $cannotDisposeOfSharePoolingToken = SharePoolingException::insufficientQuantity(
        sharePoolingId: $this->sharePoolingId,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $moreSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('150', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('150'),
        disposalProceeds: new FiatAmount('300', FiatCurrency::GBP),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('100'), position: 1) // $moreSharePoolingTokensAcquired
            ->make([
                'date' => $disposeOfSharePoolingToken->date,
                'quantity' => $disposeOfSharePoolingToken->quantity,
                'costBasis' => new FiatAmount('200', FiatCurrency::GBP),
                'disposalProceeds' => $disposeOfSharePoolingToken->disposalProceeds,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $someSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('50'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            disposalProceeds: new FiatAmount('75', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    // When

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('25'),
        costBasis: new FiatAmount('20', FiatCurrency::GBP),
    );

    // Then

    $sharePoolingTokenDisposalReverted = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal,
    );

    $moreSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: $acquireMoreSharePoolingToken->date,
            quantity: $acquireMoreSharePoolingToken->quantity,
            costBasis: $acquireMoreSharePoolingToken->costBasis,
            thirtyDayQuantity: new Quantity('25'),
        ))->setPosition(2),
    );

    $correctedSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingTokenDisposedOf1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('25', FiatCurrency::GBP),
            disposalProceeds: new FiatAmount('50', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $sharePoolingTokenAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('60', FiatCurrency::GBP),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingTokenDisposal1Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf1Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    // When

    $acquireSharePoolingToken4 = new AcquireSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('20'),
        costBasis: new FiatAmount('40', FiatCurrency::GBP),
    );

    // Then

    $sharePoolingTokenDisposal1Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposal2Reverted = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenAcquired4 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: $acquireSharePoolingToken4->date,
            quantity: $acquireSharePoolingToken4->quantity,
            costBasis: $acquireSharePoolingToken4->costBasis,
            thirtyDayQuantity: new Quantity('20'),
        ))->setPosition(5),
    );

    $sharePoolingTokenDisposedOf1Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: (SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingTokenAcquired4
            ->make(['costBasis' => new FiatAmount('120', FiatCurrency::GBP)])
        )->setPosition(2),
    );

    $sharePoolingTokenDisposedOf2Corrected = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: (SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('15'), position: 5) // $sharePoolingTokenAcquired4
            ->make([
                'costBasis' => new FiatAmount('40', FiatCurrency::GBP),
                'sameDayQuantityBreakdown' => new QuantityBreakdown(),
            ])
        )->setPosition(3),
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingTokenDisposedOf1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
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
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withThirtyDayQuantity(new Quantity('15'), position: 5) // $sharePoolingTokenAcquired4
            ->make([
                'date' => LocalDate::parse('2015-10-25'),
                'quantity' => new Quantity('25'),
                'costBasis' => new FiatAmount('40', FiatCurrency::GBP),
            ]),
    );

    $sharePoolingTokenAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('60', FiatCurrency::GBP),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingTokenDisposal1Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf1Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenAcquired4 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-29'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('40', FiatCurrency::GBP),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingTokenDisposal1Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposal2Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf1Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf1Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf2Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal,
    );

    // When

    $disposeOfSharePoolingToken3 = new DisposeOfSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('10'),
        disposalProceeds: new FiatAmount('30', FiatCurrency::GBP),
    );

    // Then

    $sharePoolingTokenDisposal2Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf2Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: (SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingTokenAcquired4
            ->make(['costBasis' => new FiatAmount('30', FiatCurrency::GBP)])
        )->setPosition(3),
    );

    $sharePoolingTokenDisposedOf3 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: (SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('10'), position: 5) // $sharePoolingTokenAcquired4
            ->make([
                'date' => $disposeOfSharePoolingToken3->date,
                'quantity' => $disposeOfSharePoolingToken3->quantity,
                'costBasis' => new FiatAmount('20', FiatCurrency::GBP),
                'disposalProceeds' => $disposeOfSharePoolingToken3->disposalProceeds,
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
