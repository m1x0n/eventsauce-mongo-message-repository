<?php

declare(strict_types=1);

namespace EventSauceExtensionsTests;

use MongoDB\Client;
use MongoDB\Database;

class DatabaseConnection
{
    public static function create(string $database): Database
    {
        $client = new Client(
            null,
            [
                'username' => 'root',
                'password' => 'example'
            ]
        );

        return $client->selectDatabase($database);
    }
}
