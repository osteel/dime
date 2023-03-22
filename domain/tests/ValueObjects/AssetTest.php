<?php

use Domain\ValueObjects\Asset;

it('cannot instantiate an asset symbol', function (string $symbol, bool $isNft, string $result) {
    expect((new Asset($symbol, $isNft))->symbol)->toBe($result);
})->with([
    'scenario 1' => ['FOO ', false, 'FOO'],
    'scenario 2' => [' FOO', true, 'FOO'],
    'scenario 3' => [' foo ', false, 'FOO'],
    'scenario 4' => [' foo ', true, 'foo'],
]);
