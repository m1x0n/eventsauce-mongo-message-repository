<?php

declare(strict_types=1);

namespace EventSauceExtensionsTests;

use EventSauce\EventSourcing\Serialization\SerializablePayload;

class UpcastedTestEvent implements SerializablePayload
{
    private $data;

    private function __construct(string $data)
    {
        $this->data = $data;
    }

    public function toPayload(): array
    {
        return [
            'data' => $this->data
        ];
    }

    public static function fromPayload(array $payload): SerializablePayload
    {
        return new self($payload['data']);
    }
}
