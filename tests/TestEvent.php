<?php

declare(strict_types=1);

namespace EventSauceExtensionsTests;

use EventSauce\EventSourcing\Serialization\SerializablePayload;

class TestEvent implements SerializablePayload
{
    public function toPayload(): array
    {
        return [];
    }

    public static function fromPayload(array $payload): SerializablePayload
    {
        return new self();
    }
}
