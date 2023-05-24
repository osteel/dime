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
use Domain\Aggregates\SharePoolingAsset\Exceptions\SharePoolingAssetException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\Enums\FiatCurrency;
use Domain\Tests\Aggregates\SharePoolingAsset\SharePoolingAssetTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

use function EventSauce\EventSourcing\PestTooling\expectToFail;
use function EventSauce\EventSourcing\PestTooling\given;
use function EventSauce\EventSourcing\PestTooling\then;
use function EventSauce\EventSourcing\PestTooling\when;

uses(SharePoolingAssetTestCase::class);

it('can acquire a share pooling asset', function () {
    when($acquireSharePoolingAsset = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('100'),
    ));

    then(
        new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP),
        new SharePoolingAssetAcquired(
            acquisition: new SharePoolingAssetAcquisition(
                id: $acquireSharePoolingAsset->transactionId,
                date: $acquireSharePoolingAsset->date,
                quantity: new Quantity('100'),
                costBasis: $acquireSharePoolingAsset->costBasis,
            ),
        ),
    );
});

it('can acquire more of the same share pooling asset', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    when($acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('300'),
    ));

    then(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            id: $acquireMoreSharePoolingAsset->transactionId,
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('300'),
        ),
    ));
});

it('cannot acquire more of the same share pooling asset because the currencies don\'t match', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    when($acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        costBasis: new FiatAmount('300', FiatCurrency::EUR),
    ));

    expectToFail(SharePoolingAssetException::currencyMismatch(
        action: $acquireMoreSharePoolingAsset,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    ));
});

it('cannot acquire more of the same share pooling asset because the transaction is older than the previous one', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given($someSharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    when($acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-20'),
        quantity: new Quantity('100'),
        costBasis: FiatAmount::GBP('100'),
    ));

    expectToFail(SharePoolingAssetException::olderThanPreviousTransaction(
        action: $acquireMoreSharePoolingAsset,
        previousTransactionDate: $someSharePoolingAssetAcquired->acquisition->date,
    ));
});

it('can dispose of a share pooling asset', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('200'),
        ),
    ));

    when($disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('50'),
        proceeds: FiatAmount::GBP('150'),
    ));

    then(new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            id: $disposeOfSharePoolingAsset->transactionId,
            date: $disposeOfSharePoolingAsset->date,
            quantity: $disposeOfSharePoolingAsset->quantity,
            costBasis: FiatAmount::GBP('100'),
            proceeds: $disposeOfSharePoolingAsset->proceeds,
        ),
    ));
});

it('cannot dispose of a share pooling asset because the currencies don\'t match', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    when($disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('100'),
        proceeds: new FiatAmount('100', FiatCurrency::EUR),
    ));

    expectToFail(SharePoolingAssetException::currencyMismatch(
        action: $disposeOfSharePoolingAsset,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    ));
});

it('cannot dispose of a share pooling asset because the transaction is older than the previous one', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given($sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    when($disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-20'),
        quantity: new Quantity('100'),
        proceeds: FiatAmount::GBP('100'),
    ));

    expectToFail(SharePoolingAssetException::olderThanPreviousTransaction(
        action: $disposeOfSharePoolingAsset,
        previousTransactionDate: $sharePoolingAssetAcquired->acquisition->date,
    ));
});

it('cannot dispose of a share pooling asset because the quantity is too high', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    when($disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('101'),
        proceeds: FiatAmount::GBP('100'),
    ));

    expectToFail(SharePoolingAssetException::insufficientQuantity(
        asset: $disposeOfSharePoolingAsset->asset,
        disposalQuantity: $disposeOfSharePoolingAsset->quantity,
        availableQuantity: new Quantity('100'),
    ));
});

it('can use the average cost basis per unit of a section 104 pool to calculate the cost basis of a disposal', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-23'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('65'),
        ),
    ));

    when($disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-25'),
        quantity: new Quantity('20'),
        proceeds: FiatAmount::GBP('40'),
    ));

    then(new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()->make([
            'id' => $disposeOfSharePoolingAsset->transactionId,
            'date' => $disposeOfSharePoolingAsset->date,
            'quantity' => $disposeOfSharePoolingAsset->quantity,
            'costBasis' => FiatAmount::GBP('22'),
            'proceeds' => $disposeOfSharePoolingAsset->proceeds,
        ]),
    ));
});

it('can dispose of a share pooling asset on the same day they were acquired', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    given($moreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('150'),
        ),
    ));

    when($disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('150'),
        proceeds: FiatAmount::GBP('300'),
    ));

    then(new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('100'), id: $moreSharePoolingAssetsAcquired->acquisition->id)
            ->make([
                'id' => $disposeOfSharePoolingAsset->transactionId,
                'date' => $disposeOfSharePoolingAsset->date,
                'quantity' => $disposeOfSharePoolingAsset->quantity,
                'costBasis' => FiatAmount::GBP('200'),
                'proceeds' => $disposeOfSharePoolingAsset->proceeds,
            ]),
    ));
});

it('can use the average cost basis per unit of a section 104 pool to calculate the cost basis of a disposal on the same day as an acquisition', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-23'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('65'),
        ),
    ));

    given(new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('22'),
            proceeds: FiatAmount::GBP('40'),
        ),
    ));

    given($evenMoreSharePoolingAssetsAcquired = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('30'),
            costBasis: FiatAmount::GBP('36'),
        ),
    ));

    when($disposeOfMoreSharePoolingAsset = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-26'),
        quantity: new Quantity('60'),
        proceeds: FiatAmount::GBP('70'),
    ));

    then(new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), id: $evenMoreSharePoolingAssetsAcquired->acquisition->id)
            ->make([
                'id' => $disposeOfMoreSharePoolingAsset->transactionId,
                'date' => $disposeOfMoreSharePoolingAsset->date,
                'quantity' => $disposeOfMoreSharePoolingAsset->quantity,
                'costBasis' => FiatAmount::GBP('69'),
                'proceeds' => $disposeOfMoreSharePoolingAsset->proceeds,
            ]),
    ));
});

it('can acquire a share pooling asset within 30 days of their disposal', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    given($someSharePoolingAssetsDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-26'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
        ),
    ));

    when($acquireMoreSharePoolingAsset = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('25'),
        costBasis: FiatAmount::GBP('20'),
    ));

    then(
        new SharePoolingAssetDisposalReverted(disposal: $someSharePoolingAssetsDisposedOf->disposal),
        new SharePoolingAssetAcquired(
            acquisition: new SharePoolingAssetAcquisition(
                id: $acquireMoreSharePoolingAsset->transactionId,
                date: $acquireMoreSharePoolingAsset->date,
                quantity: $acquireMoreSharePoolingAsset->quantity,
                costBasis: $acquireMoreSharePoolingAsset->costBasis,
                thirtyDayQuantity: new Quantity('25'),
            ),
        ),
        new SharePoolingAssetDisposedOf(
            disposal: SharePoolingAssetDisposal::factory()
                ->copyFrom($someSharePoolingAssetsDisposedOf->disposal)
                ->withThirtyDayQuantity(new Quantity('25'), id: $acquireMoreSharePoolingAsset->transactionId)
                ->make([
                    'costBasis' => FiatAmount::GBP('45'),
                    'sameDayQuantityAllocation' => new QuantityAllocation(),
                ]),
        ),
    );
});

it('can acquire a share pooling asset several times within 30 days of their disposal', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    given($sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('50'),
            sameDayQuantity: new Quantity('25'),
        ),
    ));

    given($sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('25'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('75'),
            ]),
    ));

    given($sharePoolingAssetDisposedOf2 = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-25'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('25'),
            proceeds: FiatAmount::GBP('50'),
        ),
    ));

    given($sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('60'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    ));

    given(new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf1->disposal));

    given($sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1->disposal)
            ->withThirtyDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('115')]),
    ));

    when($acquireSharePoolingAsset4 = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('20'),
        costBasis: FiatAmount::GBP('40'),
    ));

    then(
        new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf1Corrected1->disposal),
        new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf2->disposal),
        $sharePoolingAssetAcquired4 = new SharePoolingAssetAcquired(
            acquisition: new SharePoolingAssetAcquisition(
                id: $acquireSharePoolingAsset4->transactionId,
                date: $acquireSharePoolingAsset4->date,
                quantity: $acquireSharePoolingAsset4->quantity,
                costBasis: $acquireSharePoolingAsset4->costBasis,
                thirtyDayQuantity: new Quantity('20'),
            ),
        ),
        new SharePoolingAssetDisposedOf(
            disposal: SharePoolingAssetDisposal::factory()
                ->copyFrom($sharePoolingAssetDisposedOf1Corrected1->disposal)
                ->withThirtyDayQuantity(new Quantity('5'), id: $sharePoolingAssetAcquired4->acquisition->id)
                ->make(['costBasis' => FiatAmount::GBP('120')]),
        ),
        new SharePoolingAssetDisposedOf(
            disposal: SharePoolingAssetDisposal::factory()
                ->copyFrom($sharePoolingAssetDisposedOf2->disposal)
                ->withThirtyDayQuantity(new Quantity('15'), id: $sharePoolingAssetAcquired4->acquisition->id)
                ->make(['costBasis' => FiatAmount::GBP('40')]),
        ),
    );
});

it('can dispose of a share pooling asset on the same day as an acquisition within 30 days of another disposal', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    given($sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('25'),
            costBasis: FiatAmount::GBP('50'),
            sameDayQuantity: new Quantity('25'),
        ),
    ));

    given($sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('25'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('75'),
            ]),
    ));

    given($sharePoolingAssetDisposedOf2 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->make([
                'date' => LocalDate::parse('2015-10-25'),
                'quantity' => new Quantity('25'),
                'costBasis' => FiatAmount::GBP('25'),
            ]),
    ));

    given($sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-28'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('60'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    ));

    given(new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf1->disposal));

    given($sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1->disposal)
            ->withThirtyDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('115')]),
    ));

    given($sharePoolingAssetAcquired4 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-29'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('40'),
            thirtyDayQuantity: new Quantity('20'),
        ),
    ));

    given(new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf1Corrected1->disposal));

    given(new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf2->disposal));

    given(new SharePoolingAssetDisposedOf(disposal: $sharePoolingAssetDisposedOf1Corrected1->disposal));

    given(new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1Corrected1->disposal)
            ->withThirtyDayQuantity(new Quantity('5'), id: $sharePoolingAssetAcquired4->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('120')]),
    ));

    given($sharePoolingAssetDisposedOf2Corrected1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf2->disposal)
            ->withThirtyDayQuantity(new Quantity('15'), id: $sharePoolingAssetAcquired4->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('40')]),
    ));

    when($disposeOfSharePoolingAsset3 = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-29'),
        quantity: new Quantity('10'),
        proceeds: FiatAmount::GBP('30'),
    ));

    then(
        new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf2Corrected1->disposal),
        new SharePoolingAssetDisposedOf(
            disposal: SharePoolingAssetDisposal::factory()
                ->revert($sharePoolingAssetDisposedOf2->disposal)
                ->withThirtyDayQuantity(new Quantity('5'), id: $sharePoolingAssetAcquired4->acquisition->id)
                ->make(['costBasis' => FiatAmount::GBP('30')]),
        ),
        new SharePoolingAssetDisposedOf(
            disposal: SharePoolingAssetDisposal::factory()
                ->withSameDayQuantity(new Quantity('10'), id: $sharePoolingAssetAcquired4->acquisition->id)
                ->make([
                    'id' => $disposeOfSharePoolingAsset3->transactionId,
                    'date' => $disposeOfSharePoolingAsset3->date,
                    'quantity' => $disposeOfSharePoolingAsset3->quantity,
                    'costBasis' => FiatAmount::GBP('20'),
                    'proceeds' => $disposeOfSharePoolingAsset3->proceeds,
                    'thirtyDayQuantityAllocation' => new QuantityAllocation(),
                ]),
        ),
    );
});

it('can acquire a same-day share pooling asset several times on the same day as their disposal', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    given($sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
        ),
    ));

    given(new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf->disposal));

    given($sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('25'),
            sameDayQuantity: new Quantity('20'), // $sharePoolingAssetDisposedOf
        ),
    ));

    given($sharePoolingAssetDisposedOfCorrected = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->revert($sharePoolingAssetDisposedOf->disposal)
            ->withSameDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->make([
                'date' => LocalDate::parse('2015-10-22'),
                'quantity' => new Quantity('50'),
                'costBasis' => FiatAmount::GBP('55'),
            ]),
    ));

    when($acquireSharePoolingAsset3 = new AcquireSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('10'),
        costBasis: FiatAmount::GBP('14'),
    ));

    then(
        new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOfCorrected->disposal),
        $sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
            acquisition: new SharePoolingAssetAcquisition(
                id: $acquireSharePoolingAsset3->transactionId,
                date: $acquireSharePoolingAsset3->date,
                quantity: $acquireSharePoolingAsset3->quantity,
                costBasis: $acquireSharePoolingAsset3->costBasis,
                sameDayQuantity: new Quantity('10'), // $sharePoolingAssetDisposedOf
            ),
        ),
        new SharePoolingAssetDisposedOf(
            disposal: SharePoolingAssetDisposal::factory()
                ->revert($sharePoolingAssetDisposedOfCorrected->disposal)
                ->withSameDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired2->acquisition->id)
                ->withSameDayQuantity(new Quantity('10'), id: $sharePoolingAssetAcquired3->acquisition->id)
                ->make(['costBasis' => FiatAmount::GBP('59')]),
        ),
    );
});

it('can dispose of a same-day share pooling asset several times on the same day as several acquisitions', function () {
    given(new SharePoolingAssetFiatCurrencySet(FiatCurrency::GBP));

    given(new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP('100'),
        ),
    ));

    given($sharePoolingAssetDisposedOf1 = new SharePoolingAssetDisposedOf(
        disposal: new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('50'),
            costBasis: FiatAmount::GBP('50'),
            proceeds: FiatAmount::GBP('75'),
        ),
    ));

    given(new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf1->disposal));

    given($sharePoolingAssetAcquired2 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('20'),
            costBasis: FiatAmount::GBP('25'),
            sameDayQuantity: new Quantity('20'), // $sharePoolingAssetDisposedOf1
        ),
    ));

    given($sharePoolingAssetDisposedOf1Corrected1 = new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1->disposal)
            ->withSameDayQuantity(new Quantity('20'), id: $sharePoolingAssetAcquired2->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('55')]),
    ));

    given(new SharePoolingAssetDisposalReverted(disposal: $sharePoolingAssetDisposedOf1Corrected1->disposal));

    given($sharePoolingAssetAcquired3 = new SharePoolingAssetAcquired(
        acquisition: new SharePoolingAssetAcquisition(
            date: LocalDate::parse('2015-10-22'),
            quantity: new Quantity('60'),
            costBasis: FiatAmount::GBP('90'),
            sameDayQuantity: new Quantity('30'), // $sharePoolingAssetDisposedOf1
        ),
    ));

    given(new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->copyFrom($sharePoolingAssetDisposedOf1Corrected1->disposal)
            ->withSameDayQuantity(new Quantity('30'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make(['costBasis' => FiatAmount::GBP('71.875')]),
    ));

    when($disposeOfSharePoolingAsset2 = new DisposeOfSharePoolingAsset(
        transactionId: SharePoolingAssetTransactionId::generate(),
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-22'),
        quantity: new Quantity('40'),
        proceeds: FiatAmount::GBP('50'),
    ));

    then(new SharePoolingAssetDisposedOf(
        disposal: SharePoolingAssetDisposal::factory()
            ->withSameDayQuantity(new Quantity('30'), id: $sharePoolingAssetAcquired3->acquisition->id)
            ->make([
                'id' => $disposeOfSharePoolingAsset2->transactionId,
                'date' => $disposeOfSharePoolingAsset2->date,
                'quantity' => $disposeOfSharePoolingAsset2->quantity,
                'costBasis' => FiatAmount::GBP('53.125'),
                'proceeds' => $disposeOfSharePoolingAsset2->proceeds,
            ]),
    ));
});
