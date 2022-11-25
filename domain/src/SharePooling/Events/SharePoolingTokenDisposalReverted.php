<?php

namespace Domain\SharePooling\Events;

use Domain\SharePooling\SharePoolingId;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;

final class SharePoolingTokenDisposalReverted
{
    public function __construct(
        public readonly SharePoolingId $sharePoolingId,
        public readonly SharePoolingTokenDisposal $sharePoolingTokenDisposal,
    ) {
    }
}
