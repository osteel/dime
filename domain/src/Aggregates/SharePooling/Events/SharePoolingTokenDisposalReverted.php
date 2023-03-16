<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Events;

use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingTokenDisposalReverted implements SerializablePayload
{
    public function __construct(
        public SharePoolingTokenDisposal $sharePoolingTokenDisposal,
    ) {
    }

    /** @return array<string,array<string,string|int|bool|null|array<string,string|array<string>>>> */
    public function toPayload(): array
    {
        return ['share_pooling_token_disposal' => $this->sharePoolingTokenDisposal->toPayload()];
    }

    /** @param array<string,array<string,string|int|bool|null|array<string,string|array<string>>>> $payload */
    public static function fromPayload(array $payload): static
    {
        // @phpstan-ignore-next-line
        return new static(SharePoolingTokenDisposal::fromPayload($payload['share_pooling_token_disposal']));
    }
}
