<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\SharePooling\Exceptions\SharePoolingException;
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

    $sharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: $acquireSharePoolingToken->date,
            quantity: new Quantity('100'),
            costBasis: $acquireSharePoolingToken->costBasis,
        ),
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireSharePoolingToken)
        ->then($sharePoolingTokensAcquired);
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
    $sharePoolingTokensAcquired = new SharePoolingTokenAcquired(
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

    $sharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $disposeOfSharePoolingToken->date,
            quantity: $disposeOfSharePoolingToken->quantity,
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
            disposalProceeds: $disposeOfSharePoolingToken->disposalProceeds,
            sameDayQuantity: Quantity::zero(),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: $disposeOfSharePoolingToken->quantity,
        ))->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokensAcquired)
        ->when($disposeOfSharePoolingToken)
        ->then($sharePoolingTokensDisposedOf);
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
    $sharePoolingTokensAcquired = new SharePoolingTokenAcquired(
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

    $cannotDisposeOfSharePoolingToken = SharePoolingException::insufficientQuantityAvailable(
        sharePoolingId: $this->sharePoolingId,
        disposalQuantity: $disposeOfSharePoolingToken->quantity,
        availableQuantity: new Quantity('100'),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokensAcquired)
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

    $sharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $disposeOfSharePoolingToken->date,
            quantity: $disposeOfSharePoolingToken->quantity,
            costBasis: new FiatAmount('200', FiatCurrency::GBP),
            disposalProceeds: $disposeOfSharePoolingToken->disposalProceeds,
            sameDayQuantity: new Quantity('100'),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: new Quantity('50'),
        ))->setPosition(2),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokensAcquired, $moreSharePoolingTokensAcquired)
        ->when($disposeOfSharePoolingToken)
        ->then($sharePoolingTokensDisposedOf);
});

it('can acquire some share pooling tokens within 30 days of their disposal', function () {
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
            sameDayQuantity: Quantity::zero(),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: new Quantity('50'),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('25'),
        costBasis: new FiatAmount('20', FiatCurrency::GBP),
    );

    $sharePoolingTokenDisposalReverted = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal)
            ->make()
            ->setPosition(1),
    );

    $moreSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: $acquireMoreSharePoolingToken->date,
            quantity: $acquireMoreSharePoolingToken->quantity,
            costBasis: $acquireMoreSharePoolingToken->costBasis,
        ))->setPosition(2),
    );

    $correctedSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal)
            ->make([
                'costBasis' => new FiatAmount('45', FiatCurrency::GBP),
                'sameDayQuantity' => Quantity::zero(),
                'thirtyDayQuantity' => new Quantity('25'),
                'section104PoolQuantity' => new Quantity('25'),
            ])
            ->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokensAcquired, $someSharePoolingTokensDisposedOf)
        ->when($acquireMoreSharePoolingToken)
        ->then($sharePoolingTokenDisposalReverted, $moreSharePoolingTokenAcquired, $correctedSharePoolingTokensDisposedOf);
});

it('can acquire some share pooling tokens several times within 30 days of their disposal', function () {
    $sharePoolingTokensAcquired1 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokensAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokensDisposedOf1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: new FiatAmount('75', FiatCurrency::GBP),
            disposalProceeds: new FiatAmount('150', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('25'),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingTokensDisposedOf2 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('25', FiatCurrency::GBP),
            disposalProceeds: new FiatAmount('50', FiatCurrency::GBP),
            sameDayQuantity: Quantity::zero(),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingTokensAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('60', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenDisposal1Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokensDisposedOf1->sharePoolingTokenDisposal)
            ->make()
            ->setPosition(3),
    );

    $sharePoolingTokensDisposedOf1Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokensDisposedOf1->sharePoolingTokenDisposal)
            ->make([
                'costBasis' => new FiatAmount('115', FiatCurrency::GBP),
                'sameDayQuantity' => new Quantity('25'),
                'thirtyDayQuantity' => new Quantity('20'),
                'section104PoolQuantity' => new Quantity('5'),
            ]),
    );

    $acquireSharePoolingToken4 = new AcquireSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('20'),
        costBasis: new FiatAmount('40', FiatCurrency::GBP),
    );

    $sharePoolingTokenDisposal1Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokensDisposedOf1->sharePoolingTokenDisposal)
            ->make()
            ->setPosition(3),
    );

    $sharePoolingTokenDisposal2Reverted = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokensDisposedOf2->sharePoolingTokenDisposal)
            ->make()
            ->setPosition(4),
    );

    $sharePoolingTokensAcquired4 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: $acquireSharePoolingToken4->date,
            quantity: $acquireSharePoolingToken4->quantity,
            costBasis: $acquireSharePoolingToken4->costBasis,
        ),
    );

    $sharePoolingTokensDisposedOf1Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokensDisposedOf1->sharePoolingTokenDisposal)
            ->make([
                'costBasis' => new FiatAmount('120', FiatCurrency::GBP),
                'sameDayQuantity' => new Quantity('25'),
                'thirtyDayQuantity' => new Quantity('25'),
                'section104PoolQuantity' => Quantity::zero(),
            ]),
    );

    $sharePoolingTokensDisposedOf2Corrected = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokensDisposedOf2->sharePoolingTokenDisposal)
            ->make([
                'costBasis' => new FiatAmount('40', FiatCurrency::GBP),
                'sameDayQuantity' => Quantity::zero(),
                'thirtyDayQuantity' => new Quantity('15'),
                'section104PoolQuantity' => new Quantity('10'),
            ]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
            $sharePoolingTokensAcquired1,
            $sharePoolingTokensAcquired2,
            $sharePoolingTokensDisposedOf1,
            $sharePoolingTokensDisposedOf2,
            $sharePoolingTokenDisposal1Reverted1,
            $sharePoolingTokensAcquired3,
            $sharePoolingTokensDisposedOf1Corrected1,
        )
        ->when($acquireSharePoolingToken4)
        ->then(
            $sharePoolingTokenDisposal1Reverted2,
            $sharePoolingTokenDisposal2Reverted,
            $sharePoolingTokensAcquired4,
            $sharePoolingTokensDisposedOf1Corrected2,
            $sharePoolingTokensDisposedOf2Corrected,
        );
});

it('can dispose of some share pooling tokens on the same day as an acquisition within 30 days of another disposal', function () {
    $sharePoolingTokensAcquired1 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokensAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokensDisposedOf1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: new FiatAmount('75', FiatCurrency::GBP),
            disposalProceeds: new FiatAmount('150', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('25'),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingTokensDisposedOf2 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('25'),
            costBasis: new FiatAmount('25', FiatCurrency::GBP),
            disposalProceeds: new FiatAmount('50', FiatCurrency::GBP),
            sameDayQuantity: Quantity::zero(),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingTokensAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('60', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenDisposal1Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokensDisposedOf1->sharePoolingTokenDisposal)
            ->make()
            ->setPosition(3),
    );

    $sharePoolingTokensDisposedOf1Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokensDisposedOf1->sharePoolingTokenDisposal)
            ->make([
                'costBasis' => new FiatAmount('115', FiatCurrency::GBP),
                'sameDayQuantity' => new Quantity('25'),
                'thirtyDayQuantity' => new Quantity('20'),
                'section104PoolQuantity' => new Quantity('5'),
            ]),
    );

    $sharePoolingTokensAcquired4 = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-29'),
            quantity: new Quantity('20'),
            costBasis: new FiatAmount('40', FiatCurrency::GBP),
        ),
    );

    $sharePoolingTokenDisposal1Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokensDisposedOf1->sharePoolingTokenDisposal)
            ->make()
            ->setPosition(3),
    );

    $sharePoolingTokenDisposal2Reverted1 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokensDisposedOf2->sharePoolingTokenDisposal)
            ->make()
            ->setPosition(4),
    );

    $sharePoolingTokensDisposedOf1Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokensDisposedOf1->sharePoolingTokenDisposal)
            ->make([
                'costBasis' => new FiatAmount('120', FiatCurrency::GBP),
                'sameDayQuantity' => new Quantity('25'),
                'thirtyDayQuantity' => new Quantity('25'),
                'section104PoolQuantity' => Quantity::zero(),
            ]),
    );

    $sharePoolingTokensDisposedOf2Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokensDisposedOf2->sharePoolingTokenDisposal)
            ->make([
                'costBasis' => new FiatAmount('40', FiatCurrency::GBP),
                'sameDayQuantity' => Quantity::zero(),
                'thirtyDayQuantity' => new Quantity('15'),
                'section104PoolQuantity' => new Quantity('10'),
            ]),
    );

    $disposeOfSharePoolingToken3 = new DisposeOfSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('10'),
        disposalProceeds: new FiatAmount('30', FiatCurrency::GBP),
    );

    $sharePoolingTokenDisposal2Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokensDisposedOf2->sharePoolingTokenDisposal)
            ->make()
            ->setPosition(4),
    );

    $sharePoolingTokensDisposedOf2Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokensDisposedOf2->sharePoolingTokenDisposal)
            ->make([
                'costBasis' => new FiatAmount('30', FiatCurrency::GBP),
                'sameDayQuantity' => Quantity::zero(),
                'thirtyDayQuantity' => new Quantity('5'),
                'section104PoolQuantity' => new Quantity('20'),
            ]),
    );

    $sharePoolingTokensDisposedOf3 = new SharePoolingTokenDisposedOf(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: $disposeOfSharePoolingToken3->date,
            quantity: $disposeOfSharePoolingToken3->quantity,
            costBasis: new FiatAmount('20', FiatCurrency::GBP),
            disposalProceeds: $disposeOfSharePoolingToken3->disposalProceeds,
            sameDayQuantity: new Quantity('10'),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: Quantity::zero(),
        ),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
            $sharePoolingTokensAcquired1,
            $sharePoolingTokensAcquired2,
            $sharePoolingTokensDisposedOf1,
            $sharePoolingTokensDisposedOf2,
            $sharePoolingTokenDisposal1Reverted1,
            $sharePoolingTokensAcquired3,
            $sharePoolingTokensDisposedOf1Corrected1,
            $sharePoolingTokenDisposal1Reverted2,
            $sharePoolingTokenDisposal2Reverted1,
            $sharePoolingTokensAcquired4,
            $sharePoolingTokensDisposedOf1Corrected2,
            $sharePoolingTokensDisposedOf2Corrected1,
        )
        ->when($disposeOfSharePoolingToken3)
        ->then($sharePoolingTokenDisposal2Reverted2, $sharePoolingTokensDisposedOf2Corrected2, $sharePoolingTokensDisposedOf3);
});
