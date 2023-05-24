<?php

use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\ValueObjects\FiatAmount;

it('can instantiate a capital gain and return whether it is a gain or a loss', function (string $costBasis, string $proceeds, string $difference, bool $isGain, bool $isLoss) {
    $capitalGain = new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds));

    expect((string) $capitalGain->difference->quantity)->toBe($difference);
    expect($capitalGain->isGain())->toBe($isGain);
    expect($capitalGain->isLoss())->toBe($isLoss);
})->with([
    'positive' => ['1', '2', '1', true, false],
    'negative' => ['2', '1', '-1', false, true],
    'zero' => ['1', '1', '0', true, false],
]);

it('can return a capital gain with opposite quantities', function (string $from, string $to) {
    $capitalGain = (new CapitalGain(FiatAmount::GBP($from), FiatAmount::GBP($from)))->opposite();

    expect((string) $capitalGain->costBasis->quantity)->toBe($to);
    expect((string) $capitalGain->proceeds->quantity)->toBe($to);
    expect((string) $capitalGain->difference->quantity)->toBe('0');
})->with([
    'positive' => ['1', '-1'],
    'negative' => ['-1', '1'],
    'zero' => ['0', '-0'],
]);

it('can tell whether two capital gains are equal', function (string $costBasis1, string $proceeds1, string $costBasis2, string $proceeds2, bool $result) {
    $capitalGain1 = new CapitalGain(FiatAmount::GBP($costBasis1), FiatAmount::GBP($proceeds1));
    $capitalGain2 = new CapitalGain(FiatAmount::GBP($costBasis2), FiatAmount::GBP($proceeds2));

    expect($capitalGain1->isEqualTo($capitalGain2))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1', '1', true],
    'scenario 2' => ['1', '0', '1', '1', false],
    'scenario 3' => ['1', '1', '1', '0', false],
    'scenario 4' => ['1', '0', '0', '1', false],
    'scenario 5' => ['0', '1', '1', '0', false],
    'scenario 6' => ['2', '1', '2', '1', true],
    'scenario 7' => ['3', '2', '2', '1', false],
]);

it('can add a capital gain to another one', function (array $quantities1, array $quantities2, array $result) {
    $capitalGain1 = new CapitalGain(FiatAmount::GBP($quantities1[0]), FiatAmount::GBP($quantities1[1]));
    $capitalGain2 = new CapitalGain(FiatAmount::GBP($quantities2[0]), FiatAmount::GBP($quantities2[1]));

    $capitalGain = $capitalGain1->plus($capitalGain2);

    expect((string) $capitalGain->costBasis->quantity)->toBe($result[0]);
    expect((string) $capitalGain->proceeds->quantity)->toBe($result[1]);
    expect((string) $capitalGain->difference->quantity)->toBe($result[2]);
})->with([
    'scenario 1' => [['1', '1'], ['1', '1'], ['2', '2', '0']],
    'scenario 2' => [['-1', '-1'], ['-1', '-1'], ['-2', '-2', '0']],
    'scenario 3' => [['0', '0'], ['0', '0'], ['0', '0', '0']],
    'scenario 4' => [['-0', '-0'], ['-0', '-0'], ['0', '0', '0']],
    'scenario 5' => [['1', '1'], ['0', '1'], ['1', '2', '1']],
    'scenario 6' => [['1', '1'], ['1', '0'], ['2', '1', '-1']],
]);

it('can substract a capital gain from another one', function (array $quantities1, array $quantities2, array $result) {
    $capitalGain1 = new CapitalGain(FiatAmount::GBP($quantities1[0]), FiatAmount::GBP($quantities1[1]));
    $capitalGain2 = new CapitalGain(FiatAmount::GBP($quantities2[0]), FiatAmount::GBP($quantities2[1]));

    $capitalGain = $capitalGain1->minus($capitalGain2);

    expect((string) $capitalGain->costBasis->quantity)->toBe($result[0]);
    expect((string) $capitalGain->proceeds->quantity)->toBe($result[1]);
    expect((string) $capitalGain->difference->quantity)->toBe($result[2]);
})->with([
    'scenario 1' => [['1', '1'], ['1', '1'], ['0', '0', '0']],
    'scenario 2' => [['-1', '-1'], ['-1', '-1'], ['0', '0', '0']],
    'scenario 3' => [['0', '0'], ['0', '0'], ['0', '0', '0']],
    'scenario 4' => [['-0', '-0'], ['-0', '-0'], ['0', '0', '0']],
    'scenario 5' => [['1', '1'], ['0', '1'], ['1', '0', '-1']],
    'scenario 6' => [['1', '1'], ['1', '0'], ['0', '1', '1']],
]);
