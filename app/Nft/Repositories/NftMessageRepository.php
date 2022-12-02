<?php

declare(strict_types=1);

namespace App\Nft\Repositories;

use Domain\Nft\Repositories\NftMessageRepository as NftMessageRepositoryInterface;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateUuidV4MessageRepository;

class NftMessageRepository extends IlluminateUuidV4MessageRepository implements NftMessageRepositoryInterface
{
}
