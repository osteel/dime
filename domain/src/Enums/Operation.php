<?php

declare(strict_types=1);

namespace Domain\Enums;

enum Operation: string
{
    case Receive = 'receive';
    case Send = 'send';
    case Swap = 'swap';
    case Transfer = 'transfer';
}
