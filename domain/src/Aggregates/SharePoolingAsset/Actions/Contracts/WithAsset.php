<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Actions\Contracts;

use Domain\ValueObjects\Asset;

interface WithAsset
{
    public function getAsset(): Asset;
}