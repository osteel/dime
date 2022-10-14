<?php

use Domain\Services\Math;

it('can add amounts', function (array $operands, string $result) {
    expect(Math::add(...$operands))->toBe($result);
})->with([
    'scenario 1' => [['1', '1'], '2'],
    'scenario 2' => [['1', '1', '1'], '3'],
    'scenario 3' => [['1.11', '1.11'], '2.22'],
    'scenario 4' => [['1.12345678', '1.123456789'], '2.246913569'],
    'scenario 5' => [['1.11111119', '1.11111111'], '2.2222223'],
]);

it('can divide amounts', function (string $dividend, string $divisor, string $result) {
    expect(Math::div($dividend, $divisor))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1'],
    'scenario 2' => ['2', '1', '2'],
    'scenario 3' => ['2', '2', '1'],
    'scenario 4' => ['0.0000000022', '0.1', '0.000000022'],
    'scenario 5' => ['0.00000000222351', '0.105473', '0.00000002108131938979644079527462'],
]);
