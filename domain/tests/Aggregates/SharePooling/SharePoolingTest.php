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
        costBasis: FiatAmount::GBP('100'),
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
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('300'),
    );

    $moreSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: (new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('300'),
        ))->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokenAcquired)
        ->when($acquireMoreSharePoolingToken)
        ->then($moreSharePoolingTokenAcquired);
});

it('cannot acquire more of the same share pooling tokens because the currencies don\'t match', function () {
    $someSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::EUR),
    );

    $cannotAcquireSharePoolingToken = SharePoolingException::currencyMismatch(
        sharePoolingId: $this->aggregateRootId,
        action: $acquireMoreSharePoolingToken,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokenAcquired)
        ->when($acquireMoreSharePoolingToken)
        ->expectToFail($cannotAcquireSharePoolingToken);
});

it('cannot acquire more of the same share pooling tokens because the transaction is older than the previous one', function () {
    $someSharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-20'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('100'),
    );

    $cannotAcquireSharePoolingToken = SharePoolingException::olderThanPreviousTransaction(
        sharePoolingId: $this->aggregateRootId,
        action: $acquireMoreSharePoolingToken,
        previousTransactionDate: $someSharePoolingTokenAcquired->sharePoolingTokenAcquisition->date,
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
            costBasis: FiatAmount::GBP('200'),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('50'),
        proceeds: FiatAmount::GBP('150'),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: (new SharePoolingTokenDisposal(
            date: $disposeOfSharePoolingToken->date,
            quantity: $disposeOfSharePoolingToken->quantity,
            costBasis: FiatAmount::GBP('100'),
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

it('cannot dispose of some share pooling tokens because the currencies don\'t match', function () {
    $sharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('100'),
        proceeds: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotDisposeOfSharePoolingToken = SharePoolingException::currencyMismatch(
        sharePoolingId: $this->aggregateRootId,
        action: $disposeOfSharePoolingToken,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingTokenAcquired)
        ->when($disposeOfSharePoolingToken)
        ->expectToFail($cannotDisposeOfSharePoolingToken);
});

it('cannot dispose of some share pooling tokens because the transaction is older than the previous one', function () {
    $sharePoolingTokenAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-20'),
        quantity: new Quantity('100'),
        proceeds: FiatAmount::GBP('100'),
    );

    $cannotDisposeOfSharePoolingToken = SharePoolingException::olderThanPreviousTransaction(
        sharePoolingId: $this->aggregateRootId,
        action: $disposeOfSharePoolingToken,
        previousTransactionDate: $sharePoolingTokenAcquired->sharePoolingTokenAcquisition->date,
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
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('101'),
        proceeds: FiatAmount::GBP('100'),
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

it('can use the average cost basis per unit of a section 104 pool to calculate the cost basis of a disposal', function () {
    $someSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-23'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('65'),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('20'),
        proceeds: FiatAmount::GBP('40'),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->make([
                'date' => $disposeOfSharePoolingToken->date,
                'quantity' => $disposeOfSharePoolingToken->quantity,
                'costBasis' => FiatAmount::GBP('22'),
                'proceeds' => $disposeOfSharePoolingToken->proceeds,
            ])
            ->setPosition(2)
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokensAcquired, $moreSharePoolingTokensAcquired)
        ->when($disposeOfSharePoolingToken)
        ->then($sharePoolingTokenDisposedOf);
});

it('can dispose of some share pooling tokens on the same day they were acquired', function () {
    $someSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('150'),
        ),
    );

    $disposeOfSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('150'),
        proceeds: FiatAmount::GBP('300'),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('100'), position: 1) // $moreSharePoolingTokensAcquired
            ->make([
                'date' => $disposeOfSharePoolingToken->date,
                'quantity' => $disposeOfSharePoolingToken->quantity,
                'costBasis' => FiatAmount::GBP('200'),
                'proceeds' => $disposeOfSharePoolingToken->proceeds,
            ])
            ->setPosition(2)
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingTokensAcquired, $moreSharePoolingTokensAcquired)
        ->when($disposeOfSharePoolingToken)
        ->then($sharePoolingTokenDisposedOf);
});

it('can use the average cost basis per unit of a section 104 pool to calculate the cost basis of a disposal on the same day as an acquisition', function () {
    // Given

    $someSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-23'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('65'),
        ),
    );

    $someSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('22'),
            proceeds: FiatAmount::GBP('40'),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $evenMoreSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('30'),
            costBasis: FiatAmount::GBP('36'),
        ),
    );

    // When

    $disposeOfMoreSharePoolingToken = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('60'),
        proceeds: FiatAmount::GBP('70'),
    );

    // Then

    $moreSharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), position: 3) // $evenMoreSharePoolingTokensAcquired
            ->make([
                'date' => $disposeOfMoreSharePoolingToken->date,
                'quantity' => $disposeOfMoreSharePoolingToken->quantity,
                'costBasis' => FiatAmount::GBP('69'),
                'proceeds' => $disposeOfMoreSharePoolingToken->proceeds,
            ])
            ->setPosition(4)
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $someSharePoolingTokensAcquired,
        $moreSharePoolingTokensAcquired,
        $someSharePoolingTokensDisposedOf,
        $evenMoreSharePoolingTokensAcquired
    )
        ->when($disposeOfMoreSharePoolingToken)
        ->then($moreSharePoolingTokenDisposedOf);
});

it('can acquire some share pooling tokens within 30 days of their disposal', function () {
    // Given

    $someSharePoolingTokensAcquired = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $someSharePoolingTokensDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    // When

    $acquireMoreSharePoolingToken = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('25'),
        costBasis: FiatAmount::GBP('20'),
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
                'costBasis' => FiatAmount::GBP('45'),
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
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingTokenAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('50'),
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
                'costBasis' => FiatAmount::GBP('115'),
            ]),
    );

    $sharePoolingTokenDisposedOf2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('25'),
            proceeds: FiatAmount::GBP('50'),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $sharePoolingTokenAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('60'),
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
        costBasis: FiatAmount::GBP('40'),
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
            ->make(['costBasis' => FiatAmount::GBP('120')])
            ->setPosition(2),
    );

    $sharePoolingTokenDisposedOf2Corrected = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->copyFrom($sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('15'), position: 5) // $sharePoolingTokenAcquired4
            ->make([
                'costBasis' => FiatAmount::GBP('40'),
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
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingTokenAcquired2 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('50'),
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
                'costBasis' => FiatAmount::GBP('120'),
            ]),
    );

    $sharePoolingTokenDisposedOf2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withThirtyDayQuantity(new Quantity('15'), position: 5) // $sharePoolingTokenAcquired4
            ->make([
                'date' => LocalDate::parse('2015-10-25'),
                'quantity' => new Quantity('25'),
                'costBasis' => FiatAmount::GBP('40'),
            ]),
    );

    $sharePoolingTokenAcquired3 = new SharePoolingTokenAcquired(
        sharePoolingTokenAcquisition: new SharePoolingTokenAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('60'),
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
            costBasis: FiatAmount::GBP('40'),
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
        proceeds: FiatAmount::GBP('30'),
    );

    // Then

    $sharePoolingTokenDisposal2Reverted2 = new SharePoolingTokenDisposalReverted(
        sharePoolingTokenDisposal: $sharePoolingTokenDisposedOf2Corrected1->sharePoolingTokenDisposal,
    );

    $sharePoolingTokenDisposedOf2Corrected2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->revert($sharePoolingTokenDisposedOf2->sharePoolingTokenDisposal)
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingTokenAcquired4
            ->make(['costBasis' => FiatAmount::GBP('30')])
            ->setPosition(3),
    );

    $sharePoolingTokenDisposedOf3 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: (
            SharePoolingTokenDisposal::factory()
                ->withSameDayQuantity(new Quantity('10'), position: 5) // $sharePoolingTokenAcquired4
                ->make([
                    'date' => $disposeOfSharePoolingToken3->date,
                    'quantity' => $disposeOfSharePoolingToken3->quantity,
                    'costBasis' => FiatAmount::GBP('20'),
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
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
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
            costBasis: FiatAmount::GBP('25'),
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
                'costBasis' => FiatAmount::GBP('55'),
            ])
            ->setPosition(1),
    );

    // When

    $acquireSharePoolingToken3 = new AcquireSharePoolingToken(
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('10'),
        costBasis: FiatAmount::GBP('14'),
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
                'costBasis' => FiatAmount::GBP('59'),
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
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingTokenDisposedOf1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
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
            costBasis: FiatAmount::GBP('25'),
            sameDayQuantity: new Quantity('20'), // $sharePoolingTokenDisposedOf1
        ),
    );

    $sharePoolingTokenDisposedOf1Corrected1 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingTokenAcquired2
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('55'),
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
            costBasis: FiatAmount::GBP('90'),
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
                'costBasis' => FiatAmount::GBP('71.875'),
            ])
            ->setPosition(1),
    );

    // When

    $disposeOfSharePoolingToken2 = new DisposeOfSharePoolingToken(
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('40'),
        proceeds: FiatAmount::GBP('50'),
    );

    // Then

    $sharePoolingTokenDisposedOf2 = new SharePoolingTokenDisposedOf(
        sharePoolingTokenDisposal: (
            SharePoolingTokenDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), position: 3) // $sharePoolingTokenAcquired3
            ->make([
                'date' => $disposeOfSharePoolingToken2->date,
                'quantity' => $disposeOfSharePoolingToken2->quantity,
                'costBasis' => FiatAmount::GBP('53.125'),
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
