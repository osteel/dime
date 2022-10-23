<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Section104Pool\Actions\AcquireSection104PoolToken;
use Domain\Section104Pool\Actions\DisposeOfSection104PoolToken;
use Domain\Section104Pool\Events\Section104PoolTokenAcquired;
use Domain\Section104Pool\Events\Section104PoolTokenDisposedOf;
use Domain\Section104Pool\Exceptions\Section104PoolException;
use Domain\Tests\Section104Pool\Section104PoolTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(Section104PoolTestCase::class);

beforeEach(function () {
    $this->section104PoolId = $this->aggregateRootId();
});

it('can acquire some section 104 pool tokens', function () {
    $acquireSection104PoolToken = new AcquireSection104PoolToken(
        section104PoolId: $this->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $section104PoolTokensAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $acquireSection104PoolToken->section104PoolId,
        date: $acquireSection104PoolToken->date,
        quantity: '100',
        costBasis: $acquireSection104PoolToken->costBasis,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireSection104PoolToken)
        ->then($section104PoolTokensAcquired);
});

it('can acquire more of the same section 104 pool tokens', function () {
    $someSection104PoolTokenAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $this->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $acquireMoreSection104PoolToken = new AcquireSection104PoolToken(
        section104PoolId: $someSection104PoolTokenAcquired->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('300', FiatCurrency::GBP),
    );

    $moreSection104PoolTokenAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $acquireMoreSection104PoolToken->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('300', FiatCurrency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokenAcquired)
        ->when($acquireMoreSection104PoolToken)
        ->then($moreSection104PoolTokenAcquired);
});

it('cannot acquire more of the same section 104 pool tokens because currencies don\'t match', function () {
    $someSection104PoolTokenAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $this->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $acquireMoreSection104PoolToken = new AcquireSection104PoolToken(
        section104PoolId: $someSection104PoolTokenAcquired->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('300', FiatCurrency::EUR),
    );

    $cannotAcquireSection104PoolToken = Section104PoolException::cannotAcquireFromDifferentFiatCurrency(
        section104PoolId: $someSection104PoolTokenAcquired->section104PoolId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokenAcquired)
        ->when($acquireMoreSection104PoolToken)
        ->expectToFail($cannotAcquireSection104PoolToken);
});

it('can dispose of some section 104 pool tokens', function () {
    $section104PoolTokensAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $this->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('200', FiatCurrency::GBP),
    );

    $disposeOfSection104PoolToken = new DisposeOfSection104PoolToken(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        date: LocalDate::parse('2015-10-25'),
        quantity: '50',
        disposalProceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    $section104PoolTokensDisposedOf = new Section104PoolTokenDisposedOf(
        section104PoolId: $disposeOfSection104PoolToken->section104PoolId,
        date: $disposeOfSection104PoolToken->date,
        quantity: $disposeOfSection104PoolToken->quantity,
        disposalProceeds: $disposeOfSection104PoolToken->disposalProceeds,
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($section104PoolTokensAcquired)
        ->when($disposeOfSection104PoolToken)
        ->then($section104PoolTokensDisposedOf);
});

it('cannot dispose of some section 104 pool tokens because currencies don\'t match', function () {
    $section104PoolTokenAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $this->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $disposeOfSection104PoolToken = new DisposeOfSection104PoolToken(
        section104PoolId: $section104PoolTokenAcquired->section104PoolId,
        date: LocalDate::parse('2015-10-25'),
        quantity: '100',
        disposalProceeds: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotDisposeOfSection104PoolToken = Section104PoolException::cannotDisposeOfFromDifferentFiatCurrency(
        section104PoolId: $section104PoolTokenAcquired->section104PoolId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($section104PoolTokenAcquired)
        ->when($disposeOfSection104PoolToken)
        ->expectToFail($cannotDisposeOfSection104PoolToken);
});

it('cannot dispose of some section 104 pool tokens because the quantity is too high', function () {
    $section104PoolTokensAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $this->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $disposeOfSection104PoolToken = new DisposeOfSection104PoolToken(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        date: LocalDate::parse('2015-10-25'),
        quantity: '101',
        disposalProceeds: new FiatAmount('100', FiatCurrency::GBP),
    );

    $cannotDisposeOfSection104PoolToken = Section104PoolException::disposalQuantityIsTooHigh(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        disposalQuantity: $disposeOfSection104PoolToken->quantity,
        availableQuantity: '100',
    );

    /** @var AggregateRootTestCase $this */
    $this->given($section104PoolTokensAcquired)
        ->when($disposeOfSection104PoolToken)
        ->expectToFail($cannotDisposeOfSection104PoolToken);
});

it('can dispose of some section 104 pool tokens on the same day they were acquired', function () {
    $someSection104PoolTokensAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $this->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $moreSection104PoolTokensAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $someSection104PoolTokensAcquired->section104PoolId,
        date: LocalDate::parse('2015-10-26'),
        quantity: '100',
        costBasis: new FiatAmount('150', FiatCurrency::GBP),
    );

    $disposeOfSection104PoolToken = new DisposeOfSection104PoolToken(
        section104PoolId: $someSection104PoolTokensAcquired->section104PoolId,
        date: LocalDate::parse('2015-10-26'),
        quantity: '50',
        disposalProceeds: new FiatAmount('100', FiatCurrency::GBP),
    );

    $section104PoolTokensDisposedOf = new Section104PoolTokenDisposedOf(
        section104PoolId: $disposeOfSection104PoolToken->section104PoolId,
        date: $disposeOfSection104PoolToken->date,
        quantity: '50',
        disposalProceeds: new FiatAmount('100', FiatCurrency::GBP),
        costBasis: new FiatAmount('75', FiatCurrency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokensAcquired, $moreSection104PoolTokensAcquired)
        ->when($disposeOfSection104PoolToken)
        ->then($section104PoolTokensDisposedOf);
});

it('can acquire some section 104 pool tokens within 30 days of their disposal', function () {
    $someSection104PoolTokensAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $this->section104PoolId,
        date: LocalDate::parse('2015-10-21'),
        quantity: '100',
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $someSection104PoolTokensDisposedOf = new Section104PoolTokenDisposedOf(
        section104PoolId: $someSection104PoolTokensAcquired->section104PoolId,
        date: LocalDate::parse('2015-10-26'),
        quantity: '50',
        disposalProceeds: new FiatAmount('75', FiatCurrency::GBP),
        costBasis: new FiatAmount('50', FiatCurrency::GBP),
    );

    $acquireMoreSection104PoolToken = new AcquireSection104PoolToken(
        section104PoolId: $someSection104PoolTokensDisposedOf->section104PoolId,
        date: LocalDate::parse('2015-10-29'),
        quantity: '25',
        costBasis: new FiatAmount('20', FiatCurrency::GBP),
    );

    $moreSection104PoolTokenAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $acquireMoreSection104PoolToken->section104PoolId,
        date: $acquireMoreSection104PoolToken->date,
        quantity: $acquireMoreSection104PoolToken->quantity,
        costBasis: $acquireMoreSection104PoolToken->costBasis,
    );

    $section104PoolTokenDisposalReverted = new Section104PoolTokenDisposalReverted(
        section104PoolId: $acquireMoreSection104PoolToken->section104PoolId,
        date: $someSection104PoolTokensDisposedOf->date,
        quantity: $someSection104PoolTokensDisposedOf->quantity,
        disposalProceeds: $someSection104PoolTokensDisposedOf->disposalProceeds,
        costBasis: $someSection104PoolTokensDisposedOf->costBasis,
    );

    $correctedSection104PoolTokensDisposedOf = new Section104PoolTokenDisposedOf(
        section104PoolId: $section104PoolTokenDisposalReverted->section104PoolId,
        date: $section104PoolTokenDisposalReverted->date,
        quantity: $section104PoolTokenDisposalReverted->quantity,
        disposalProceeds: $section104PoolTokenDisposalReverted->disposalProceeds,
        costBasis: new FiatAmount('45', FiatCurrency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokensAcquired, $someSection104PoolTokensDisposedOf)
        ->when($acquireMoreSection104PoolToken)
        ->then($moreSection104PoolTokenAcquired, $section104PoolTokenDisposalReverted, $correctedSection104PoolTokensDisposedOf);
});
