<?php

declare(strict_types=1);

namespace EventSauceExtensionsTests;

use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Upcasting\DelegatableUpcaster;
use Generator;

class TestUpcaster implements DelegatableUpcaster
{
    public function type(): string
    {
        return 'test';
    }

    public function canUpcast(string $type, array $message): bool
    {
        return true;
    }

    public function upcast(array $message): Generator
    {
        // Upcasting example

        // Extending payload
        $message['payload']['data'] = 'upcasted';

        // Converting to new event type
        $message['headers'][Header::EVENT_TYPE] = 'event_sauce_extensions_tests.upcasted_test_event';

        yield $message;
    }
}
