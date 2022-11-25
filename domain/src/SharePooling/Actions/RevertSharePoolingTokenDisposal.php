<?php

namespace Domain\SharePooling\Actions;

use Domain\SharePooling\SharePoolingId;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;

final class RevertSharePoolingTokenDisposal
{
    public function __construct(
        public readonly SharePoolingId $sharePoolingId,
        public readonly SharePoolingTokenDisposal $sharePoolingTokenDisposal,
    ) {
    }
}
