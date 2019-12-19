<?php

declare(strict_types=1);

namespace EventSauceExtensions;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use Generator;
use IteratorIterator;
use MongoDB\Database;
use MongoDB\Driver\Cursor;

use function count;

/**
 * @package EventSauceExtensions
 */
class MongoDbMessageRepository implements MessageRepository
{
    private const MONGODB_SORT = 'sort';
    private const MONGODB_SORT_ASCENDING = 1;
    private const MONGODB_GREATER_THAN = '$gt';

    /**
     * @var Database
     */
    private $database;

    /**
     * @var MongoDbMessageSerializer
     */
    private $messageSerializer;

    /**
     * @var string $collectionName
     */
    private $collectionName;

    public function __construct(
        Database $database,
        MongoDbMessageSerializer $messageSerializer,
        string $collectionName
    ) {
        $this->database = $database;
        $this->messageSerializer = $messageSerializer;
        $this->collectionName = $collectionName;
    }

    /**
     * @param Message ...$messages
     */
    public function persist(Message ...$messages)
    {
        if (count($messages) === 0) {
            return;
        }

        $documents = array_map(function (Message $message) {
            return $this->messageSerializer->serializeMessage($message);
        }, $messages);

        $this->database
            ->selectCollection($this->collectionName)
            ->insertMany($documents);
    }

    /**
     * @param AggregateRootId $id
     * @return Generator
     */
    public function retrieveAll(AggregateRootId $id): Generator
    {
        $cursor = $this->database
            ->selectCollection($this->collectionName)
            ->find(
                [
                    MongoDbMessageSerializer::AGGREGATE_ROOT_ID => $id->toString(),
                ],
                [
                    self::MONGODB_SORT => [
                        MongoDbMessageSerializer::AGGREGATE_ROOT_VERSION => self::MONGODB_SORT_ASCENDING,
                    ]
                ]
            );

        return $this->yieldMessagesFromCursor($cursor);
    }

    /**
     * @param AggregateRootId $id
     * @param int $aggregateRootVersion
     * @return Generator
     */
    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $cursor = $this->database
            ->selectCollection($this->collectionName)
            ->find(
                [
                    MongoDbMessageSerializer::AGGREGATE_ROOT_ID => $id->toString(),
                    MongoDbMessageSerializer::AGGREGATE_ROOT_VERSION => [
                        self::MONGODB_GREATER_THAN => $aggregateRootVersion,
                    ]
                ],
                [
                    self::MONGODB_SORT => [
                        MongoDbMessageSerializer::AGGREGATE_ROOT_VERSION => self::MONGODB_SORT_ASCENDING,
                    ]
                ]
            );

        return $this->yieldMessagesFromCursor($cursor);
    }

    /**
     * @param Cursor $cursor
     * @return Generator
     */
    private function yieldMessagesFromCursor(Cursor $cursor): Generator
    {
        $iterator = new IteratorIterator($cursor);
        $iterator->rewind();

        while ($iterator->valid()) {
            $document = $iterator->current();

            $payload = json_decode(json_encode($document), true);

            $messages = $this->messageSerializer->unserializePayload($payload);

            foreach ($messages as $message) {
                yield $message;
            }

            $iterator->next();
        }

        return isset($message)
            ? $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0
            : 0;
    }
}
