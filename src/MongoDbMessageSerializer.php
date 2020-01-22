<?php

declare(strict_types=1);

namespace EventSauceExtensions;

use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\PointInTime;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\Upcasting\Upcaster;
use Generator;
use MongoDB\BSON\UTCDateTime;
use Ramsey\Uuid\Uuid;

class MongoDbMessageSerializer implements MessageSerializer
{
    private const EVENT_ID = 'event_id';
    private const EVENT_TYPE = 'event_type';
    private const TIME_OF_RECORDING = 'time_of_recording';
    public const AGGREGATE_ROOT_ID = 'aggregate_root_id';
    public const AGGREGATE_ROOT_VERSION = 'aggregate_root_version';

    /**
     * @var ConstructingMessageSerializer
     */
    private $serializer;

    /**
     * @var Upcaster
     */
    private $upcaster;

    public function __construct(
        ConstructingMessageSerializer $serializer,
        ?Upcaster $upcaster = null
    ) {
        $this->serializer = $serializer;
        $this->upcaster = $upcaster ?: new NullUpcaster();
    }

    public function serializeMessage(Message $message): array
    {
        $serialized = $this->serializer->serializeMessage($message);

        $extra = [
            self::EVENT_ID => $serialized['headers'][Header::EVENT_ID] ?? Uuid::uuid4()->toString(),
            self::EVENT_TYPE => $serialized['headers'][Header::EVENT_TYPE],
            self::AGGREGATE_ROOT_ID => $serialized['headers'][Header::AGGREGATE_ROOT_ID],
            self::AGGREGATE_ROOT_VERSION => $serialized['headers'][Header::AGGREGATE_ROOT_VERSION],
            self::TIME_OF_RECORDING => new UTCDateTime(
                (PointInTime::fromString($serialized['headers'][Header::TIME_OF_RECORDING]))->dateTime()
            )
        ];

        return array_merge($extra, $serialized);
    }

    public function unserializePayload(array $payload): Generator
    {
        $payload[self::TIME_OF_RECORDING] = new UTCDateTime(
            (PointInTime::fromString($payload['headers'][Header::TIME_OF_RECORDING]))->dateTime()
        );

        if ($this->upcaster->canUpcast($payload['headers'][Header::EVENT_TYPE], $payload)) {
            foreach ($this->upcaster->upcast($payload) as $upcastedPayload) {
                yield from $this->serializer->unserializePayload($upcastedPayload);
            }
        } else {
            yield from $this->serializer->unserializePayload($payload);
        }
    }
}
