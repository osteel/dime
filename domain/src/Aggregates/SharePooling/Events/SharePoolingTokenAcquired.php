<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Events;

use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingTokenAcquired implements SerializablePayload
{
    public function __construct(
        public SharePoolingTokenAcquisition $sharePoolingTokenAcquisition,
    ) {
    }

    /** @return array{share_pooling_token_acquisition:array{date:string,quantity:string,cost_basis:array{quantity:string,currency:string},same_day_quantity:string,thirty_day_quantity:string,position:int|null}} */
    public function toPayload(): array
    {
        return ['share_pooling_token_acquisition' => $this->sharePoolingTokenAcquisition->toPayload()];
    }

    /** @param array{share_pooling_token_acquisition:array{date:string,quantity:string,cost_basis:array{quantity:string,currency:string},same_day_quantity:string,thirty_day_quantity:string,position:int}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(SharePoolingTokenAcquisition::fromPayload($payload['share_pooling_token_acquisition']));
    }
}
