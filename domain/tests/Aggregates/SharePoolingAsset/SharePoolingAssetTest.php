<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\SharePoolingAsset\Exceptions\SharePoolingAssetException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityBreakdown;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposal;
use Domain\Enums\FiatCurrency;
use Domain\Tests\Aggregates\SharePoolingAsset\SharePoolingAssetTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(SharePoolingAssetTestCase::class);

it('can acquire a share pooling asset', function () {
    $acquireSharePoolingAsset = new AcquireSharePoolingAsset(
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('100'),
    );

    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: $acquireSharePoolingAsset->date,
            quantity: new Quantity('100'),
            costBasis: $acquireSharePoolingAsset->costBasis,
        ),
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireSharePoolingAsset)
        ->then($sharePoolingAssetAcquired);
});

it('can acquire more of the same share pooling asset', function () {
    $someSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('300'),
    );

    $moreSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: (new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('300'),
        ))->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingAssetAcquired)
        ->when($acquireMoreSharePoolingAsset)
        ->then($moreSharePoolingAssetAcquired);
});

it('cannot acquire more of the same share pooling asset because the currencies don\'t match', function () {
    $someSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::EUR),
    );

    $cannotAcquireSharePoolingAsset = SharePoolingAssetException::currencyMismatch(
        sharePoolingAssetId: $this->aggregateRootId,
        action: $acquireMoreSharePoolingAsset,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingAssetAcquired)
        ->when($acquireMoreSharePoolingAsset)
        ->expectToFail($cannotAcquireSharePoolingAsset);
});

it('cannot acquire more of the same share pooling asset because the transaction is older than the previous one', function () {
    $someSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        date: LocalDate::parse('2015-10-20'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('100'),
    );

    $cannotAcquireSharePoolingAsset = SharePoolingAssetException::olderThanPreviousTransaction(
        sharePoolingAssetId: $this->aggregateRootId,
        action: $acquireMoreSharePoolingAsset,
        previousTransactionDate: $someSharePoolingAssetAcquired->sharePoolingAssetAcquisition->date,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingAssetAcquired)
        ->when($acquireMoreSharePoolingAsset)
        ->expectToFail($cannotAcquireSharePoolingAsset);
});

it('can dispose of a share pooling asset', function () {
    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('200'),
        ),
    );

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('50'),
        proceeds: FiatAmount::GBP('150'),
    );

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: (new SharePoolingAssetDisposal(
            date: $disposeOfSharePoolingAsset->date,
            quantity: $disposeOfSharePoolingAsset->quantity,
            costBasis: FiatAmount::GBP('100'),
            proceeds: $disposeOfSharePoolingAsset->proceeds,
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ))->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->then($sharePoolingAssetDisposedOf);
});

it('cannot dispose of a share pooling asset because the currencies don\'t match', function () {
    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('100'),
        proceeds: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotDisposeOfSharePoolingAsset = SharePoolingAssetException::currencyMismatch(
        sharePoolingAssetId: $this->aggregateRootId,
        action: $disposeOfSharePoolingAsset,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->expectToFail($cannotDisposeOfSharePoolingAsset);
});

it('cannot dispose of a share pooling asset because the transaction is older than the previous one', function () {
    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-20'),
        quantity: new Quantity('100'),
        proceeds: FiatAmount::GBP('100'),
    );

    $cannotDisposeOfSharePoolingAsset = SharePoolingAssetException::olderThanPreviousTransaction(
        sharePoolingAssetId: $this->aggregateRootId,
        action: $disposeOfSharePoolingAsset,
        previousTransactionDate: $sharePoolingAssetAcquired->sharePoolingAssetAcquisition->date,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->expectToFail($cannotDisposeOfSharePoolingAsset);
});

it('cannot dispose of a share pooling asset because the quantity is too high', function () {
    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('101'),
        proceeds: FiatAmount::GBP('100'),
    );

    $cannotDisposeOfSharePoolingAsset = SharePoolingAssetException::insufficientQuantity(
        sharePoolingAssetId: $this->aggregateRootId,
        disposalQuantity: $disposeOfSharePoolingAsset->quantity,
        availableQuantity: new Quantity('100'),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->expectToFail($cannotDisposeOfSharePoolingAsset);
});

it('can use the average cost basis per unit of a section 104 pool to calculate the cost basis of a disposal', function () {
    $someSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-23'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('65'),
        ),
    );

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('20'),
        proceeds: FiatAmount::GBP('40'),
    );

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->make([
                'date' => $disposeOfSharePoolingAsset->date,
                'quantity' => $disposeOfSharePoolingAsset->quantity,
                'costBasis' => FiatAmount::GBP('22'),
                'proceeds' => $disposeOfSharePoolingAsset->proceeds,
            ])
            ->setPosition(2)
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingAssetsAcquired, $moreSharePoolingAssetsAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->then($sharePoolingAssetDisposedOf);
});

it('can dispose of a share pooling asset on the same day they were acquired', function () {
    $someSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('150'),
        ),
    );

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('150'),
        proceeds: FiatAmount::GBP('300'),
    );

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('100'), position: 1) // $moreSharePoolingAssetsAcquired
            ->make([
                'date' => $disposeOfSharePoolingAsset->date,
                'quantity' => $disposeOfSharePoolingAsset->quantity,
                'costBasis' => FiatAmount::GBP('200'),
                'proceeds' => $disposeOfSharePoolingAsset->proceeds,
            ])
            ->setPosition(2)
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingAssetsAcquired, $moreSharePoolingAssetsAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->then($sharePoolingAssetDisposedOf);
});

it('can use the average cost basis per unit of a section 104 pool to calculate the cost basis of a disposal on the same day as an acquisition', function () {
    // Given

    $someSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-23'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('65'),
        ),
    );

    $someSharePoolingAssetsDisposedOf = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('22'),
            proceeds: FiatAmount::GBP('40'),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $evenMoreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('30'),
            costBasis: FiatAmount::GBP('36'),
        ),
    );

    // When

    $disposeOfMoreSharePoolingAsset = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('60'),
        proceeds: FiatAmount::GBP('70'),
    );

    // Then

    $moreSharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), position: 3) // $evenMoreSharePoolingAssetsAcquired
            ->make([
                'date' => $disposeOfMoreSharePoolingAsset->date,
                'quantity' => $disposeOfMoreSharePoolingAsset->quantity,
                'costBasis' => FiatAmount::GBP('69'),
                'proceeds' => $disposeOfMoreSharePoolingAsset->proceeds,
            ])
            ->setPosition(4)
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $someSharePoolingAssetsAcquired,
        $moreSharePoolingAssetsAcquired,
        $someSharePoolingAssetsDisposedOf,
        $evenMoreSharePoolingAssetsAcquired
    )
        ->when($disposeOfMoreSharePoolingAsset)
        ->then($moreSharePoolingAssetDisposedOf);
});

it('can acquire a share pooling asset within 30 days of their disposal', function () {
    // Given

    $someSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $someSharePoolingAssetsDisposedOf = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    // When

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('25'),
        costBasis: FiatAmount::GBP('20'),
    );

    // Then

    $sharePoolingAssetDisposalReverted = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $someSharePoolingAssetsDisposedOf->sharePoolingAssetDisposal,
    );

    $moreSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: (new SharePoolingAssetAcquisition(
            date: $acquireMoreSharePoolingAsset->date,
            quantity: $acquireMoreSharePoolingAsset->quantity,
            costBasis: $acquireMoreSharePoolingAsset->costBasis,
            thirtyDayQuantity: new Quantity('25'),
        ))->setPosition(2),
    );

    $correctedSharePoolingAssetsDisposedOf = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($someSharePoolingAssetsDisposedOf->sharePoolingAssetDisposal)
            ->withThirtyDayQuantity(new Quantity('25'), position: 2) // $acquireMoreSharePoolingAsset
            ->make([
                'costBasis' => FiatAmount::GBP('45'),
                'sameDayQuantityBreakdown' => new QuantityBreakdown(),
            ])
            ->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSharePoolingAssetsAcquired, $someSharePoolingAssetsDisposedOf)
        ->when($acquireMoreSharePoolingAsset)
        ->then($sharePoolingAssetDisposalReverted, $moreSharePoolingAssetAcquired, $correctedSharePoolingAssetsDisposedOf);
});

it('can acquire a share pooling asset several times within 30 days of their disposal', function () {
    // Given

    $sharePoolingAssetAcquired1 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('50'),
            sameDayQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('25'), position: 1) // $sharePoolingAssetAcquired2
            ->withThirtyDayQuantity(new Quantity('20'), position: 4) // $sharePoolingAssetAcquired3
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('115'),
            ]),
    );

    $sharePoolingAssetDisposedOf2 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('25'),
            proceeds: FiatAmount::GBP('50'),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('60'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingAssetDisposal1Reverted1 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1->sharePoolingAssetDisposal,
    );

    // When

    $acquireSharePoolingAsset4 = new AcquireSharePoolingAsset(
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('20'),
        costBasis: FiatAmount::GBP('40'),
    );

    // Then

    $sharePoolingAssetDisposal1Reverted2 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1Corrected1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetDisposal2Reverted = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf2->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetAcquired4 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: (new SharePoolingAssetAcquisition(
            date: $acquireSharePoolingAsset4->date,
            quantity: $acquireSharePoolingAsset4->quantity,
            costBasis: $acquireSharePoolingAsset4->costBasis,
            thirtyDayQuantity: new Quantity('20'),
        ))->setPosition(5),
    );

    $sharePoolingAssetDisposedOf1Corrected2 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1Corrected1->sharePoolingAssetDisposal)
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingAssetAcquired4
            ->make(['costBasis' => FiatAmount::GBP('120')])
            ->setPosition(2),
    );

    $sharePoolingAssetDisposedOf2Corrected = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf2->sharePoolingAssetDisposal)
            ->withThirtyDayQuantity(new Quantity('15'), position: 5) // $sharePoolingAssetAcquired4
            ->make([
                'costBasis' => FiatAmount::GBP('40'),
                'sameDayQuantityBreakdown' => new QuantityBreakdown(),
            ])
            ->setPosition(3),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetAcquired1,
        $sharePoolingAssetAcquired2,
        $sharePoolingAssetDisposedOf1,
        $sharePoolingAssetDisposedOf2,
        $sharePoolingAssetDisposal1Reverted1,
        $sharePoolingAssetAcquired3,
        $sharePoolingAssetDisposedOf1Corrected1,
    )
        ->when($acquireSharePoolingAsset4)
        ->then(
            $sharePoolingAssetDisposal1Reverted2,
            $sharePoolingAssetDisposal2Reverted,
            $sharePoolingAssetAcquired4,
            $sharePoolingAssetDisposedOf1Corrected2,
            $sharePoolingAssetDisposedOf2Corrected,
        );
});

it('can dispose of a share pooling asset on the same day as an acquisition within 30 days of another disposal', function () {
    // Given

    $sharePoolingAssetAcquired1 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('50'),
            sameDayQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('25'), position: 1) // $sharePoolingAssetAcquired2
            ->withThirtyDayQuantity(new Quantity('20'), position: 4) // $sharePoolingAssetAcquired3
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingAssetAcquired4
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('120'),
            ]),
    );

    $sharePoolingAssetDisposedOf2 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->withThirtyDayQuantity(new Quantity('15'), position: 5) // $sharePoolingAssetAcquired4
            ->make([
                'date' => LocalDate::parse('2015-10-25'),
                'quantity' => new Quantity('25'),
                'costBasis' => FiatAmount::GBP('40'),
            ]),
    );

    $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('60'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingAssetDisposal1Reverted1 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetAcquired4 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-29'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('40'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingAssetDisposal1Reverted2 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1Corrected1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetDisposal2Reverted1 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf2->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetDisposedOf1Corrected2 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1Corrected1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetDisposedOf2Corrected1 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf2->sharePoolingAssetDisposal,
    );

    // When

    $disposeOfSharePoolingAsset3 = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('10'),
        proceeds: FiatAmount::GBP('30'),
    );

    // Then

    $sharePoolingAssetDisposal2Reverted2 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf2Corrected1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetDisposedOf2Corrected2 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->revert($sharePoolingAssetDisposedOf2->sharePoolingAssetDisposal)
            ->withThirtyDayQuantity(new Quantity('5'), position: 5) // $sharePoolingAssetAcquired4
            ->make(['costBasis' => FiatAmount::GBP('30')])
            ->setPosition(3),
    );

    $sharePoolingAssetDisposedOf3 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: (
            SharePoolingAssetDisposal::factory()
                ->withSameDayQuantity(new Quantity('10'), position: 5) // $sharePoolingAssetAcquired4
                ->make([
                    'date' => $disposeOfSharePoolingAsset3->date,
                    'quantity' => $disposeOfSharePoolingAsset3->quantity,
                    'costBasis' => FiatAmount::GBP('20'),
                    'proceeds' => $disposeOfSharePoolingAsset3->proceeds,
                    'thirtyDayQuantityBreakdown' => new QuantityBreakdown(),
                ])
        )->setPosition(6),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetAcquired1,
        $sharePoolingAssetAcquired2,
        $sharePoolingAssetDisposedOf1,
        $sharePoolingAssetDisposedOf2,
        $sharePoolingAssetDisposal1Reverted1,
        $sharePoolingAssetAcquired3,
        $sharePoolingAssetDisposedOf1Corrected1,
        $sharePoolingAssetDisposal1Reverted2,
        $sharePoolingAssetDisposal2Reverted1,
        $sharePoolingAssetAcquired4,
        $sharePoolingAssetDisposedOf1Corrected2,
        $sharePoolingAssetDisposedOf2Corrected1,
    )
        ->when($disposeOfSharePoolingAsset3)
        ->then($sharePoolingAssetDisposal2Reverted2, $sharePoolingAssetDisposedOf2Corrected2, $sharePoolingAssetDisposedOf3);
});

it('can acquire a same-day share pooling asset several times on the same day as their disposal', function () {
    // Given

    $sharePoolingAssetAcquired1 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $sharePoolingAssetDisposalReverted = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('25'),
            sameDayQuantity: new Quantity('20'), // $sharePoolingAssetDisposedOf
        ),
    );

    $sharePoolingAssetDisposedOfCorrected = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->revert($sharePoolingAssetDisposedOf->sharePoolingAssetDisposal)
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingAssetAcquired2
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('55'),
            ])
            ->setPosition(1),
    );

    // When

    $acquireSharePoolingAsset3 = new AcquireSharePoolingAsset(
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('10'),
        costBasis: FiatAmount::GBP('14'),
    );

    // Then

    $sharePoolingAssetDisposalReverted2 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOfCorrected->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: (new SharePoolingAssetAcquisition(
            date: $acquireSharePoolingAsset3->date,
            quantity: $acquireSharePoolingAsset3->quantity,
            costBasis: $acquireSharePoolingAsset3->costBasis,
            sameDayQuantity: new Quantity('10'), // $sharePoolingAssetDisposedOf
        ))->setPosition(3),
    );

    $sharePoolingAssetsDisposedOfCorrected2 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->revert($sharePoolingAssetDisposedOfCorrected->sharePoolingAssetDisposal)
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingAssetAcquired2
            ->withSameDayQuantity(new Quantity('10'), position: 3) // $sharePoolingAssetAcquired3
            ->make([
                'costBasis' => FiatAmount::GBP('59'),
                'thirtyDayQuantityBreakdown' => new QuantityBreakdown(),
            ])
            ->setPosition(1),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetAcquired1,
        $sharePoolingAssetDisposedOf,
        $sharePoolingAssetDisposalReverted,
        $sharePoolingAssetAcquired2,
        $sharePoolingAssetDisposedOfCorrected,
    )
        ->when($acquireSharePoolingAsset3)
        ->then($sharePoolingAssetDisposalReverted2, $sharePoolingAssetAcquired3, $sharePoolingAssetsDisposedOfCorrected2);
});

it('can dispose of a same-day share pooling asset several times on the same day as several acquisitions', function () {
    // Given

    $sharePoolingAssetAcquired1 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    $sharePoolingAssetDisposalReverted1 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('25'),
            sameDayQuantity: new Quantity('20'), // $sharePoolingAssetDisposedOf1
        ),
    );

    $sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingAssetAcquired2
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('55'),
            ])
            ->setPosition(1),
    );

    $sharePoolingAssetDisposalReverted2 = new SharePoolingAssetDisposalReverted(
        sharePoolingAssetDisposal: $sharePoolingAssetDisposedOf1Corrected1->sharePoolingAssetDisposal,
    );

    $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        sharePoolingAssetAcquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('60'),
            costBasis: FiatAmount::GBP('90'),
            sameDayQuantity: new Quantity('30'), // $sharePoolingAssetDisposedOf1
        ),
    );

    $sharePoolingAssetDisposedOf1Corrected2 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('20'), position: 2) // $sharePoolingAssetAcquired2
            ->withSameDayQuantity(new Quantity('30'), position: 3) // $sharePoolingAssetAcquired3
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('71.875'),
            ])
            ->setPosition(1),
    );

    // When

    $disposeOfSharePoolingAsset2 = new DisposeOfSharePoolingAsset(
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('40'),
        proceeds: FiatAmount::GBP('50'),
    );

    // Then

    $sharePoolingAssetDisposedOf2 = new SharePoolingAssetDisposedOf(
        sharePoolingAssetDisposal: (
            SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), position: 3) // $sharePoolingAssetAcquired3
            ->make([
                'date' => $disposeOfSharePoolingAsset2->date,
                'quantity' => $disposeOfSharePoolingAsset2->quantity,
                'costBasis' => FiatAmount::GBP('53.125'),
                'proceeds' => $disposeOfSharePoolingAsset2->proceeds,
            ])
        )->setPosition(4),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetAcquired1,
        $sharePoolingAssetDisposedOf1,
        $sharePoolingAssetDisposalReverted1,
        $sharePoolingAssetAcquired2,
        $sharePoolingAssetDisposedOf1Corrected1,
        $sharePoolingAssetDisposalReverted2,
        $sharePoolingAssetAcquired3,
        $sharePoolingAssetDisposedOf1Corrected2,
    )
        ->when($disposeOfSharePoolingAsset2)
        ->then($sharePoolingAssetDisposedOf2);
});
