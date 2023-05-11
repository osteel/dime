<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetFiatCurrencySet;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetSet;
use Domain\Aggregates\SharePoolingAsset\Exceptions\SharePoolingAssetException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\Enums\FiatCurrency;
use Domain\Tests\Aggregates\SharePoolingAsset\SharePoolingAssetTestCase;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(SharePoolingAssetTestCase::class);

beforeEach(function () {
    $this->asset = new Asset('FOO');
});

it('can acquire a share pooling asset', function () {
    // When

    $acquireSharePoolingAsset = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('100'),
    );

    // Then

    $sharePoolingAssetSet = new SharePoolingAssetSet($acquireSharePoolingAsset->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            id: $acquireSharePoolingAsset->transactionId,
            asset: $acquireSharePoolingAsset->asset,
            date: $acquireSharePoolingAsset->date,
            quantity: new Quantity('100'),
            costBasis: $acquireSharePoolingAsset->costBasis,
        ),
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireSharePoolingAsset)
        ->then($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $sharePoolingAssetAcquired);
});

it('can acquire more of the same share pooling asset', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $someSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    // When

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('300'),
    );

    // Then

    $moreSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            id: $acquireMoreSharePoolingAsset->transactionId,
            asset: $acquireMoreSharePoolingAsset->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('300'),
        ),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $someSharePoolingAssetAcquired)
        ->when($acquireMoreSharePoolingAsset)
        ->then($moreSharePoolingAssetAcquired);
});

it('cannot acquire more of the same share pooling asset because the assets don\'t match', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $someSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    // When

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        asset: new Asset('BAR'),
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('100'),
    );

    // Then

    $cannotAcquireSharePoolingAsset = SharePoolingAssetException::assetMismatch(
        action: $acquireMoreSharePoolingAsset,
        incoming: $acquireMoreSharePoolingAsset->asset,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $someSharePoolingAssetAcquired)
        ->when($acquireMoreSharePoolingAsset)
        ->expectToFail($cannotAcquireSharePoolingAsset);
});

it('cannot acquire more of the same share pooling asset because the currencies don\'t match', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $someSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    // When

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::EUR),
    );

    // Then

    $cannotAcquireSharePoolingAsset = SharePoolingAssetException::currencyMismatch(
        action: $acquireMoreSharePoolingAsset,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $someSharePoolingAssetAcquired)
        ->when($acquireMoreSharePoolingAsset)
        ->expectToFail($cannotAcquireSharePoolingAsset);
});

it('cannot acquire more of the same share pooling asset because the transaction is older than the previous one', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $someSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    // When

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-20'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('100'),
    );

    // Then

    $cannotAcquireSharePoolingAsset = SharePoolingAssetException::olderThanPreviousTransaction(
        action: $acquireMoreSharePoolingAsset,
        previousTransactionDate: $someSharePoolingAssetAcquired->acquisition->date,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $someSharePoolingAssetAcquired)
        ->when($acquireMoreSharePoolingAsset)
        ->expectToFail($cannotAcquireSharePoolingAsset);
});

it('can dispose of a share pooling asset', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('200'),
        ),
    );

    // When

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('50'),
        proceeds: FiatAmount::GBP('150'),
    );

    // Then

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            id: $disposeOfSharePoolingAsset->transactionId,
            asset: $disposeOfSharePoolingAsset->asset,
            date: $disposeOfSharePoolingAsset->date,
            quantity: $disposeOfSharePoolingAsset->quantity,
            costBasis: FiatAmount::GBP('100'),
            proceeds: $disposeOfSharePoolingAsset->proceeds,
        ),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->then($sharePoolingAssetDisposedOf);
});

it('cannot dispose of a share pooling asset because the assets don\'t match', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    // When

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        asset: new Asset('BAR'),
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('100'),
        proceeds: FiatAmount::GBP('100'),
    );

    // Then

    $cannotDisposeOfSharePoolingAsset = SharePoolingAssetException::assetMismatch(
        action: $disposeOfSharePoolingAsset,
        incoming: $disposeOfSharePoolingAsset->asset,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->expectToFail($cannotDisposeOfSharePoolingAsset);
});

it('cannot dispose of a share pooling asset because the currencies don\'t match', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    // When

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('100'),
        proceeds: new FiatAmount('100', FiatCurrency::EUR),
    );

    // Then

    $cannotDisposeOfSharePoolingAsset = SharePoolingAssetException::currencyMismatch(
        action: $disposeOfSharePoolingAsset,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->expectToFail($cannotDisposeOfSharePoolingAsset);
});

it('cannot dispose of a share pooling asset because the transaction is older than the previous one', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    // When

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-20'),
        quantity: new Quantity('100'),
        proceeds: FiatAmount::GBP('100'),
    );

    // Then

    $cannotDisposeOfSharePoolingAsset = SharePoolingAssetException::olderThanPreviousTransaction(
        action: $disposeOfSharePoolingAsset,
        previousTransactionDate: $sharePoolingAssetAcquired->acquisition->date,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->expectToFail($cannotDisposeOfSharePoolingAsset);
});

it('cannot dispose of a share pooling asset because the quantity is too high', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    // When

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('101'),
        proceeds: FiatAmount::GBP('100'),
    );

    // Then

    $cannotDisposeOfSharePoolingAsset = SharePoolingAssetException::insufficientQuantity(
        asset: $disposeOfSharePoolingAsset->asset,
        disposalQuantity: $disposeOfSharePoolingAsset->quantity,
        availableQuantity: new Quantity('100'),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($sharePoolingAssetSet, $sharePoolingAssetFiatCurrencySet, $sharePoolingAssetAcquired)
        ->when($disposeOfSharePoolingAsset)
        ->expectToFail($cannotDisposeOfSharePoolingAsset);
});

it('can use the average cost basis per unit of a section 104 pool to calculate the cost basis of a disposal', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $someSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-23'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('65'),
        ),
    );

    // When

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('20'),
        proceeds: FiatAmount::GBP('40'),
    );

    // Then

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()->make([
            'id' => $disposeOfSharePoolingAsset->transactionId,
            'asset' => $disposeOfSharePoolingAsset->asset,
            'date' => $disposeOfSharePoolingAsset->date,
            'quantity' => $disposeOfSharePoolingAsset->quantity,
            'costBasis' => FiatAmount::GBP('22'),
            'proceeds' => $disposeOfSharePoolingAsset->proceeds,
        ]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetSet,
        $sharePoolingAssetFiatCurrencySet,
        $someSharePoolingAssetsAcquired,
        $moreSharePoolingAssetsAcquired,
    )
        ->when($disposeOfSharePoolingAsset)
        ->then($sharePoolingAssetDisposedOf);
});

it('can dispose of a share pooling asset on the same day they were acquired', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $someSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('150'),
        ),
    );

    // When

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('150'),
        proceeds: FiatAmount::GBP('300'),
    );

    // Then

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('100'), id: $moreSharePoolingAssetsAcquired->acquisition->id)
            ->make([
                'id' => $disposeOfSharePoolingAsset->transactionId,
                'asset' => $disposeOfSharePoolingAsset->asset,
                'date' => $disposeOfSharePoolingAsset->date,
                'quantity' => $disposeOfSharePoolingAsset->quantity,
                'costBasis' => FiatAmount::GBP('200'),
                'proceeds' => $disposeOfSharePoolingAsset->proceeds,
            ]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetSet,
        $sharePoolingAssetFiatCurrencySet,
        $someSharePoolingAssetsAcquired,
        $moreSharePoolingAssetsAcquired,
    )
        ->when($disposeOfSharePoolingAsset)
        ->then($sharePoolingAssetDisposedOf);
});

it('can use the average cost basis per unit of a section 104 pool to calculate the cost basis of a disposal on the same day as an acquisition', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $someSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $moreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-23'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('65'),
        ),
    );

    $someSharePoolingAssetsDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('22'),
            proceeds: FiatAmount::GBP('40'),
        ),
    );

    $evenMoreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('30'),
            costBasis: FiatAmount::GBP('36'),
        ),
    );

    // When

    $disposeOfMoreSharePoolingAsset = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('60'),
        proceeds: FiatAmount::GBP('70'),
    );

    // Then

    $moreSharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), id: $evenMoreSharePoolingAssetsAcquired->acquisition->id)
            ->make([
                'id' => $disposeOfMoreSharePoolingAsset->transactionId,
                'asset' => $disposeOfMoreSharePoolingAsset->asset,
                'date' => $disposeOfMoreSharePoolingAsset->date,
                'quantity' => $disposeOfMoreSharePoolingAsset->quantity,
                'costBasis' => FiatAmount::GBP('69'),
                'proceeds' => $disposeOfMoreSharePoolingAsset->proceeds,
            ]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetSet,
        $sharePoolingAssetFiatCurrencySet,
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

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $someSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $someSharePoolingAssetsDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
        ),
    );

    // When

    $acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('25'),
        costBasis: FiatAmount::GBP('20'),
    );

    // Then

    $sharePoolingAssetDisposalReverted = new SharePoolingAssetDisposalReverted(
        disposal: $someSharePoolingAssetsDisposedOf->disposal,
    );

    $moreSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            id: $acquireMoreSharePoolingAsset->transactionId,
            asset: $acquireMoreSharePoolingAsset->asset,
            date: $acquireMoreSharePoolingAsset->date,
            quantity: $acquireMoreSharePoolingAsset->quantity,
            costBasis: $acquireMoreSharePoolingAsset->costBasis,
            thirtyDayQuantity: new Quantity('25'),
        ),
    );

    $correctedSharePoolingAssetsDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($someSharePoolingAssetsDisposedOf->disposal)
            ->withThirtyDayQuantity(new Quantity('25'), id: $acquireMoreSharePoolingAsset->transactionId)
            ->make([
                'costBasis' => FiatAmount::GBP('45'),
                'sameDayQuantityAllocation' => new QuantityAllocation(),
            ]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetSet,
        $sharePoolingAssetFiatCurrencySet,
        $someSharePoolingAssetsAcquired,
        $someSharePoolingAssetsDisposedOf,
    )
        ->when($acquireMoreSharePoolingAsset)
        ->then($sharePoolingAssetDisposalReverted, $moreSharePoolingAssetAcquired, $correctedSharePoolingAssetsDisposedOf);
});

it('can acquire a share pooling asset several times within 30 days of their disposal', function () {
    // Given

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired1 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('50'),
            sameDayQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('25'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->make([
                'asset' => $sharePoolingAssetSet->asset,
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('75'),
            ]),
    );

    $sharePoolingAssetDisposedOf2 = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('25'),
            proceeds: FiatAmount::GBP('50'),
        ),
    );

    $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('60'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingAssetDisposal1Reverted1 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf1->disposal,
    );

    $sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1->disposal)
            ->withThirtyDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('115')]),
    );

    // When

    $acquireSharePoolingAsset4 = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('20'),
        costBasis: FiatAmount::GBP('40'),
    );

    // Then

    $sharePoolingAssetDisposal1Reverted2 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf1Corrected1->disposal,
    );

    $sharePoolingAssetDisposal2Reverted = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf2->disposal,
    );

    $sharePoolingAssetAcquired4 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            id: $acquireSharePoolingAsset4->transactionId,
            asset: $acquireSharePoolingAsset4->asset,
            date: $acquireSharePoolingAsset4->date,
            quantity: $acquireSharePoolingAsset4->quantity,
            costBasis: $acquireSharePoolingAsset4->costBasis,
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingAssetDisposedOf1Corrected2 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1Corrected1->disposal)
            ->withThirtyDayQuantity(new Quantity('5'), id: $sharePoolingAssetAcquired4->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('120')]),
    );

    $sharePoolingAssetDisposedOf2Corrected = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf2->disposal)
            ->withThirtyDayQuantity(new Quantity('15'), id: $sharePoolingAssetAcquired4->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('40')]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetSet,
        $sharePoolingAssetFiatCurrencySet,
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

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired1 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('50'),
            sameDayQuantity: new Quantity('25'),
        ),
    );

    $sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('25'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->make([
                'asset' => $sharePoolingAssetSet->asset,
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('75'),
            ]),
    );

    $sharePoolingAssetDisposedOf2 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->make([
                'date' => LocalDate::parse('2015-10-25'),
                'quantity' => new Quantity('25'),
                'costBasis' => FiatAmount::GBP('25'),
            ]),
    );

    $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('60'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingAssetDisposal1Reverted1 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf1->disposal,
    );

    $sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1->disposal)
            ->withThirtyDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('115')]),
    );

    $sharePoolingAssetAcquired4 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-29'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('40'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    );

    $sharePoolingAssetDisposal1Reverted2 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf1Corrected1->disposal,
    );

    $sharePoolingAssetDisposal2Reverted1 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf2->disposal,
    );

    $sharePoolingAssetDisposedOf1Corrected2 = new SharePoolingAssetDisposedOf(
        disposal: $sharePoolingAssetDisposedOf1Corrected1->disposal,
    );

    $sharePoolingAssetDisposedOf1Corrected2 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1Corrected1->disposal)
            ->withThirtyDayQuantity(new Quantity('5'), id: $sharePoolingAssetAcquired4->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('120')]),
    );

    $sharePoolingAssetDisposedOf2Corrected1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf2->disposal)
            ->withThirtyDayQuantity(new Quantity('15'), id: $sharePoolingAssetAcquired4->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('40')]),
    );

    // When

    $disposeOfSharePoolingAsset3 = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('10'),
        proceeds: FiatAmount::GBP('30'),
    );

    // Then

    $sharePoolingAssetDisposal2Reverted2 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf2Corrected1->disposal,
    );

    $sharePoolingAssetDisposedOf2Corrected2 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->revert($sharePoolingAssetDisposedOf2->disposal)
            ->withThirtyDayQuantity(new Quantity('5'), id: $sharePoolingAssetAcquired4->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('30')]),
    );

    $sharePoolingAssetDisposedOf3 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('10'), id: $sharePoolingAssetAcquired4->acquisition->id)
            ->make([
                'id' => $disposeOfSharePoolingAsset3->transactionId,
                'asset' => $disposeOfSharePoolingAsset3->asset,
                'date' => $disposeOfSharePoolingAsset3->date,
                'quantity' => $disposeOfSharePoolingAsset3->quantity,
                'costBasis' => FiatAmount::GBP('20'),
                'proceeds' => $disposeOfSharePoolingAsset3->proceeds,
                'thirtyDayQuantityAllocation' => new QuantityAllocation(),
            ]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetSet,
        $sharePoolingAssetFiatCurrencySet,
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

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired1 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
        ),
    );

    $sharePoolingAssetDisposalReverted = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf->disposal,
    );

    $sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('25'),
            sameDayQuantity: new Quantity('20'), // $sharePoolingAssetDisposedOf
        ),
    );

    $sharePoolingAssetDisposedOfCorrected = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->revert($sharePoolingAssetDisposedOf->disposal)
            ->withSameDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->make([
                'asset' => $sharePoolingAssetSet->asset,
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('55'),
            ]),
    );

    // When

    $acquireSharePoolingAsset3 = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('10'),
        costBasis: FiatAmount::GBP('14'),
    );

    // Then

    $sharePoolingAssetDisposalReverted2 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOfCorrected->disposal,
    );

    $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            id: $acquireSharePoolingAsset3->transactionId,
            asset: $acquireSharePoolingAsset3->asset,
            date: $acquireSharePoolingAsset3->date,
            quantity: $acquireSharePoolingAsset3->quantity,
            costBasis: $acquireSharePoolingAsset3->costBasis,
            sameDayQuantity: new Quantity('10'), // $sharePoolingAssetDisposedOf
        ),
    );

    $sharePoolingAssetsDisposedOfCorrected2 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->revert($sharePoolingAssetDisposedOfCorrected->disposal)
            ->withSameDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->withSameDayQuantity(new Quantity('10'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('59')]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetSet,
        $sharePoolingAssetFiatCurrencySet,
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

    $sharePoolingAssetSet = new SharePoolingAssetSet($this->asset);

    $sharePoolingAssetFiatCurrencySet = new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP);

    $sharePoolingAssetAcquired1 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    );

    $sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
        ),
    );

    $sharePoolingAssetDisposalReverted1 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf1->disposal,
    );

    $sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('25'),
            sameDayQuantity: new Quantity('20'), // $sharePoolingAssetDisposedOf1
        ),
    );

    $sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1->disposal)
            ->withSameDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('55')]),
    );

    $sharePoolingAssetDisposalReverted2 = new SharePoolingAssetDisposalReverted(
        disposal: $sharePoolingAssetDisposedOf1Corrected1->disposal,
    );

    $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            asset: $sharePoolingAssetSet->asset,
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('60'),
            costBasis: FiatAmount::GBP('90'),
            sameDayQuantity: new Quantity('30'), // $sharePoolingAssetDisposedOf1
        ),
    );

    $sharePoolingAssetDisposedOf1Corrected2 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1Corrected1->disposal)
            ->withSameDayQuantity(new Quantity('30'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('71.875')]),
    );

    // When

    $disposeOfSharePoolingAsset2 = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $sharePoolingAssetSet->asset,
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('40'),
        proceeds: FiatAmount::GBP('50'),
    );

    // Then

    $sharePoolingAssetDisposedOf2 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make([
                'id' => $disposeOfSharePoolingAsset2->transactionId,
                'asset' => $disposeOfSharePoolingAsset2->asset,
                'date' => $disposeOfSharePoolingAsset2->date,
                'quantity' => $disposeOfSharePoolingAsset2->quantity,
                'costBasis' => FiatAmount::GBP('53.125'),
                'proceeds' => $disposeOfSharePoolingAsset2->proceeds,
            ]),
    );

    /** @var AggregateRootTestCase $this */
    $this->given(
        $sharePoolingAssetSet,
        $sharePoolingAssetFiatCurrencySet,
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
