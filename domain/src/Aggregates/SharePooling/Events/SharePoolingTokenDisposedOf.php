<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Events;

use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingTokenDisposedOf implements SerializablePayload
{
    public function __construct(
        public SharePoolingTokenDisposal $sharePoolingTokenDisposal,
    ) {
    }

    /** @return array{share_pooling_token_disposal:array{date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_breakdown:array{breakdown:array<int,string>},thirty_day_quantity_breakdown:array{breakdown:array<int,string>},processed:bool,position:int|null}} */
    public function toPayload(): array
    {
        return ['share_pooling_token_disposal' => $this->sharePoolingTokenDisposal->toPayload()];
    }

    /** @param array{share_pooling_token_disposal:array{date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_breakdown:array{breakdown:array<int,string>},thirty_day_quantity_breakdown:array{breakdown:array<int,string>},processed:bool,position:int}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(SharePoolingTokenDisposal::fromPayload($payload['share_pooling_token_disposal']));
    }
}
