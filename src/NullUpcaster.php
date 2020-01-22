<?php

declare(strict_types=1);

namespace EventSauceExtensions;

use EventSauce\EventSourcing\Upcasting\Upcaster;
use Generator;

class NullUpcaster implements Upcaster
{
    public function canUpcast(string $type, array $message): bool
    {
        return false;
    }

    public function upcast(array $message): Generator
    {
        yield $message;
    }
}
