<?php

declare(strict_types=1);

namespace App\Services\TransactionReader;

use Generator;

interface TransactionReader
{
    /** @return Generator<array<string, string>> */
    public function read(string $path): Generator;
}
