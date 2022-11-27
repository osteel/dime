<?php

declare(strict_types=1);

namespace Domain\SharePooling\Actions;

use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;

final class RevertSharePoolingTokenDisposal
{
    public function __construct(
        public readonly SharePoolingTokenDisposal $sharePoolingTokenDisposal,
    ) {
    }
}
