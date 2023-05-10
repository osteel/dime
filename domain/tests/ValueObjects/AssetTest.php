<?php

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Exceptions\AssetException;

it('cannot instantiate an asset because a fiat currency is always fungible', function () {
    expect(fn () => new Asset(FiatCurrency::GBP->value, true))
        ->toThrow(AssetException::class, AssetException::fiatCurrencyIsAlwaysFungible(FiatCurrency::GBP)->getMessage());
});

it('can instantiate an asset', function (string $symbol, bool $isNonFungible, string $result) {
    expect((new Asset($symbol, $isNonFungible))->symbol)->toBe($result);
})->with([
    'scenario 1' => ['FOO ', false, 'FOO'],
    'scenario 2' => [' FOO', true, 'FOO'],
    'scenario 3' => [' foo ', false, 'FOO'],
    'scenario 4' => [' foo ', true, 'foo'],
]);

it('can instantiate a non-fungible asset', function () {
    $asset = Asset::nonFungible('foo');

    expect($asset->symbol)->toBe('foo');
    expect($asset->isNonFungible)->toBeTrue();
});

it('can tell whether two assets are the same', function (string $symbol1, bool $isNonFungible1, string $symbol2, bool $isNonFungible2, bool $result) {
    expect((new Asset($symbol1, $isNonFungible1))->is(new Asset($symbol2, $isNonFungible2)))->toBe($result);
})->with([
    'scenario 1' => ['FOO', false, 'BAR', false, false],
    'scenario 2' => ['FOO', false, 'FOO', true, false],
    'scenario 3' => ['FOO', true, 'FOO', true, true],
    'scenario 4' => ['FOO', false, FiatCurrency::GBP->value, false, false],
    'scenario 5' => [FiatCurrency::EUR->value, false, FiatCurrency::GBP->value, false, false],
    'scenario 6' => [FiatCurrency::GBP->value, false, FiatCurrency::GBP->value, false, true],
]);

it('can tell whether the asset is fiat', function () {
    expect((new Asset(FiatCurrency::GBP->value))->isFiat())->toBe(true);
    expect((new Asset('foo'))->isFiat())->toBe(false);
});
