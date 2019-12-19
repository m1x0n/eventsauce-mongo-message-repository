<?php

declare(strict_types=1);

namespace EventSauceExtensionsTests;

use MongoDB\Client;
use MongoDB\Database;

class DatabaseConnection
{
    public static function create(string $database): Database
    {
        $client = getenv('TRAVIS')
            ? new Client()
            : new Client(
                null,
                [
                    'username' => 'user',
                    'password' => 'secret'
                ]
            );

        return $client->selectDatabase($database);
    }
}
