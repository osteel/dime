<?php

namespace App\Services\DatabaseManager;

use App\Services\DatabaseManager\Exceptions\DatabaseManagerException;

interface DatabaseManagerContract
{
    /**
     * Prepare the database for transaction processing.
     *
     * @throws DatabaseManagerException
     */
    public function prepare(): void;
}
