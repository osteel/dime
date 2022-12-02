<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Actions;

use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;

final class RevertSharePoolingTokenDisposal
{
    public function __construct(
        public readonly SharePoolingTokenDisposal $sharePoolingTokenDisposal,
    ) {
    }
}
