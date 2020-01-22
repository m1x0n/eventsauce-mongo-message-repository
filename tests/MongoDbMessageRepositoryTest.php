<?php

declare(strict_types=1);

namespace EventSauceExtensionsTests;

use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Time\TestClock;
use EventSauce\EventSourcing\UuidAggregateRootId;
use EventSauceExtensions\MongoDbMessageRepository;
use EventSauceExtensions\MongoDbMessageSerializer;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class MongoDbMessageRepositoryTest extends TestCase
{
    private const TEST_DATABASE = 'eventsauce';
    private const TEST_COLLECTION = 'events';

    /**
     * @var Database
     */
    private $database;

    /**
     * @var TestClock
     */
    private $clock;
    /**
     * @var DefaultHeadersDecorator
     */
    private $decorator;

    /**
     * @var MongoDbMessageRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->database = DatabaseConnection::create(self::TEST_DATABASE);
        $this->database->selectCollection(self::TEST_COLLECTION)->drop();

        $this->clock = new TestClock();
        $this->decorator = new DefaultHeadersDecorator();
        $this->repository = new MongoDbMessageRepository(
            $this->database,
            new MongoDbMessageSerializer(new ConstructingMessageSerializer()),
            'events'
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->database->selectCollection(self::TEST_COLLECTION)->drop();
    }

    public function testShouldPersistMessagesInRepository(): void
    {
        $this->expectNotToPerformAssertions();

        $aggregateRootId = UuidAggregateRootId::create();
        $eventId = Uuid::uuid4()->toString();

        $message = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => $eventId,
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 1,
        ]));

        $this->repository->persist($message);
    }

    public function testShouldRetrieveAllMessagesFromRepository(): void
    {
        $aggregateRootId = UuidAggregateRootId::create();
        $eventId = Uuid::uuid4()->toString();

        $message = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => $eventId,
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 1,
        ]));
        $this->repository->persist($message);
        $generator = $this->repository->retrieveAll($aggregateRootId);
        $retrievedMessage = iterator_to_array($generator, false)[0];
        $this->assertEquals($message, $retrievedMessage);
    }

    public function testShouldRetrieveMessagesAfterASpecificVersion(): void
    {
        $aggregateRootId = UuidAggregateRootId::create();
        $messages = [];
        $messages[] = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 1,
        ]));
        $messages[] = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 2,
        ]));
        $messages[] = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 3,
        ]));
        $messages[] = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 4,
        ]));
        $messages[] = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => $lastEventId = Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 5,
        ]));

        $this->repository->persist(...$messages);
        $generator = $this->repository->retrieveAllAfterVersion($aggregateRootId, 3);

        /** @var Message[] $messages */
        $messages = iterator_to_array($generator, false);
        $this->assertCount(2, $messages);
        $this->assertEquals($lastEventId, $messages[1]->header(Header::EVENT_ID));
        $this->assertEquals(5, $messages[1]->header(Header::AGGREGATE_ROOT_VERSION));
    }

    public function testShouldUpcastEvents(): void
    {
        $this->repository = new MongoDbMessageRepository(
            $this->database,
            new MongoDbMessageSerializer(new ConstructingMessageSerializer(), new TestUpcaster()),
            'events'
        );

        $aggregateRootId = UuidAggregateRootId::create();
        $eventId = Uuid::uuid4()->toString();

        $message = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => $eventId,
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 1,
        ]));
        $this->repository->persist($message);
        $generator = $this->repository->retrieveAll($aggregateRootId);
        $retrievedMessage = iterator_to_array($generator, false)[0];
        $this->assertInstanceOf(UpcastedTestEvent::class, $retrievedMessage->event());
    }
}
