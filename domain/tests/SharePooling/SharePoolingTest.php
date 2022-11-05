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

it('can acquire some section 104 pool tokens', function () {
    $acquireSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $this->sharePoolingId,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $sharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $acquireSharePoolingToken->sharePoolingId,
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

it('can acquire more of the same section 104 pool tokens', function () {
    $someSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $someSharePoolingTokenAcquired->sharePoolingId,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::GBP),
    );

    $moreSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $acquireMoreSharePoolingToken->sharePoolingId,
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

it('cannot acquire more of the same section 104 pool tokens because currencies don\'t match', function () {
    $someSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $someSharePoolingTokenAcquired->sharePoolingId,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::EUR),
    );

    $cannotAcquireSharePoolingToken = SharePoolingException::cannotAcquireFromDifferentFiatCurrency(
        sharePoolingId: $someSharePoolingTokenAcquired->sharePoolingId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokenAcquired)
        ->when($acquireMoreSharePoolingToken)
        ->expectToFail($cannotAcquireSharePoolingToken);
});

it('can dispose of some section 104 pool tokens', function () {
    $sharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('200', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        sharePoolingId: $sharePoolingTokensAcquired->sharePoolingId,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('50'),
        disposalProceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    $sharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $disposeOfSharePoolingToken->sharePoolingId,
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $disposeOfSharePoolingToken->date,
            quantity: $disposeOfSharePoolingToken->quantity,
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
            disposalProceeds: $disposeOfSharePoolingToken->disposalProceeds,
            sameDayQuantity: new Quantity('0'),
            thirtyDayQuantity: new Quantity('0'),
            section104PoolQuantity: $disposeOfSharePoolingToken->quantity,
        ))->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokensAcquired)
        ->when($disposeOfSharePoolingToken)
        ->then($sharePoolingTokensDisposedOf);
});

it('cannot dispose of some section 104 pool tokens because currencies don\'t match', function () {
    $sharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        sharePoolingId: $sharePoolingTokenAcquired->sharePoolingId,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('100'),
        disposalProceeds: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotDisposeOfSharePoolingToken = SharePoolingException::cannotDisposeOfFromDifferentFiatCurrency(
        sharePoolingId: $sharePoolingTokenAcquired->sharePoolingId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokenAcquired)
        ->when($disposeOfSharePoolingToken)
        ->expectToFail($cannotDisposeOfSharePoolingToken);
});

it('cannot dispose of some section 104 pool tokens because the quantity is too high', function () {
    $sharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        sharePoolingId: $sharePoolingTokensAcquired->sharePoolingId,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('101'),
        disposalProceeds: new FiatAmount('100', FiatCurrency::GBP),
    );

    $cannotDisposeOfSharePoolingToken = SharePoolingException::insufficientQuantityAvailable(
        sharePoolingId: $sharePoolingTokensAcquired->sharePoolingId,
        disposalQuantity: $disposeOfSharePoolingToken->quantity,
        availableQuantity: new Quantity('100'),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokensAcquired)
        ->when($disposeOfSharePoolingToken)
        ->expectToFail($cannotDisposeOfSharePoolingToken);
});

it('can dispose of some section 104 pool tokens on the same day they were acquired', function () {
    $someSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $moreSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $someSharePoolingTokensAcquired->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('150', FiatCurrency::GBP),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        sharePoolingId: $someSharePoolingTokensAcquired->sharePoolingId,
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('150'),
        disposalProceeds: new FiatAmount('300', FiatCurrency::GBP),
    );

    $sharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $disposeOfSharePoolingToken->sharePoolingId,
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $disposeOfSharePoolingToken->date,
            quantity: $disposeOfSharePoolingToken->quantity,
            costBasis: new FiatAmount('200', FiatCurrency::GBP),
            disposalProceeds: $disposeOfSharePoolingToken->disposalProceeds,
            sameDayQuantity: new Quantity('100'),
            thirtyDayQuantity: new Quantity('0'),
            section104PoolQuantity: new Quantity('50'),
        ))->setPosition(2),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokensAcquired, $moreSharePoolingTokensAcquired)
        ->when($disposeOfSharePoolingToken)
        ->then($sharePoolingTokensDisposedOf);
});

it('can acquire some section 104 pool tokens within 30 days of their disposal', function () {
    $someSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $this->sharePoolingId,
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
        ),
    );

    $someSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $someSharePoolingTokensAcquired->sharePoolingId,
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('50'),
            costBasis: new FiatAmount('50', FiatCurrency::GBP),
            disposalProceeds: new FiatAmount('75', FiatCurrency::GBP),
            sameDayQuantity: new Quantity('0'),
            thirtyDayQuantity: new Quantity('0'),
            section104PoolQuantity: new Quantity('50'),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        sharePoolingId: $someSharePoolingTokensDisposedOf->sharePoolingId,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('25'),
        costBasis: new FiatAmount('20', FiatCurrency::GBP),
    );

    $sharePoolingTokenDisposalReverted = new SharePoolingTokenDisposalReverted(
        sharePoolingId: $acquireMoreSharePoolingToken->sharePoolingId,
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->date,
            quantity: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->quantity,
            costBasis: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->costBasis,
            disposalProceeds: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->disposalProceeds,
            sameDayQuantity: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->sameDayQuantity,
            thirtyDayQuantity: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->thirtyDayQuantity,
            section104PoolQuantity: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->section104PoolQuantity,
        ))->setPosition(1),
    );

    $moreSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingId: $acquireMoreSharePoolingToken->sharePoolingId,
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: $acquireMoreSharePoolingToken->date,
            quantity: $acquireMoreSharePoolingToken->quantity,
            costBasis: $acquireMoreSharePoolingToken->costBasis,
        ))->setPosition(2),
    );

    $correctedSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingId: $sharePoolingTokenDisposalReverted->sharePoolingId,
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->date,
            quantity: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->quantity,
            costBasis: new FiatAmount('45', FiatCurrency::GBP),
            disposalProceeds: $someSharePoolingTokensDisposedOf->sharePoolingTokenDisposal->disposalProceeds,
            sameDayQuantity: new Quantity('0'),
            thirtyDayQuantity: new Quantity('25'),
            section104PoolQuantity: new Quantity('25'),
        ))->setPosition(3),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokensAcquired, $someSharePoolingTokensDisposedOf)
        ->when($acquireMoreSharePoolingToken)
        ->then($sharePoolingTokenDisposalReverted, $moreSharePoolingTokenAcquired, $correctedSharePoolingTokensDisposedOf);
});
