<?php

declare(strict_types=1);

namespace App\Services\ActionRunner;

use Domain\Services\ActionRunner\ActionRunner as ActionRunnerInterface;
use Illuminate\Bus\Dispatcher;

final class ActionRunner implements ActionRunnerInterface
{
    public function run(object $action): void
    {
        /** @var Dispatcher */
        $dispatcher = resolve(Dispatcher::class);

        $dispatcher->dispatchSync($action);
    }
}
