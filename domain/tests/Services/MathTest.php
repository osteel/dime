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
