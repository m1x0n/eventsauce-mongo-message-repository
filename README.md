## eventsauce-mongo-message-repository [![Build Status](https://travis-ci.org/m1x0n/eventsauce-mongo-message-repository.svg?branch=master)](https://travis-ci.org/m1x0n/eventsauce-mongo-message-repository)
MongoDB implementation for EventSauce message repository.

### Requirements

- php: ^7.2
- ext-mongodb: ^1.6

### Installation

```
composer require m1x0n/eventsauce-mongo-message-repository
```

### Testing

```
docker-compose up -d
./vendor/bin/phpunit
```

### Usage
In order to plug-in this repository:
- MongoDB must be installed on your environment. (See `docker-compose.yaml` for example)
- `ext-mongodb` must be installed for php
- EventSauce aggregate root repository should be configured like this:

```php
$mongoDbName = 'mydb';
$eventsCollectionName = 'events';

// Initialize mongo client and select target database
$client = new MongoDB\Client(
    null,
    [
        'username' => 'user',
        'password' => 'secret'
    ]
);

$database = $client->selectDatabase($mongoDbName);

// Configure message repository or see MongoDbMessageRepositoryFactory
$messageRepository = new \EventSauceExtensions\MongoDbMessageRepository(
    $database,
    new \EventSauceExtensions\MongoDbMessageSerializer(
        new \EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer()
    ),
    $eventsCollectionName
);

// This is mostly for example and can be replaced with MessageBus dispatcher like RabbitMQ.
// See eventsauce/rabbitmq-bundle-bindings
$messageDispatcher = new \EventSauce\EventSourcing\SynchronousMessageDispatcher();

// MyAggregateClass must implement
$bulbsAggregateRepository = new \EventSauce\EventSourcing\ConstructingAggregateRootRepository(
    MyAggreateClass::class,
    $messageRepository,
    $messageDispatcher
);
```

The configuration might be slightly simplified by using dependency injection container.

### Event's document structure
Events serialization/deserialization was made using following document structure under the hood:
```
{
    "_id": ObjectId("5dfe3322e006a263d256da36"),
    "event_id": "482eacef-04f3-46c1-b88b-79457f67c778",
    "event_type": "foo.bar.baz",
    "aggregate_root_id": "3be51408-3e1e-4970-88e1-faadeb6796f3",
    "aggregate_root_version": 5,
    "time_of_recording": ISODate("2019-12-21T14:58:42.800Z"),
    "headers": {
        "__event_type": "foo.bar.baz",
        "__time_of_recording": "2019-12-21 14:58:42.800066+0000",
        "__aggregate_root_id": "3be51408-3e1e-4970-88e1-faadeb6796f3",
        "__aggregate_root_version": 5,
        "__aggregate_root_id_type": "foo.bar.id"
    },
    "payload": {
        "id": "3be51408-3e1e-4970-88e1-faadeb6796f3"
    }
}
```

The most important information is placed under `headers` and `payload` properties.

### Performance
The following unique index should be enforced for better performance either for `ConstructingAggregateRootRepository`
or `ConstructingAggregateRootRepositoryWithSnapshotting`.
```
Index := unique(aggregate_root_id + aggregate_root_version)
```

It could be done during application bootstrap:
```php
$database
    ->selectCollection('events')
    ->createIndex(
    [
        'aggregate_root_id' => 1,
        'aggregate_root_version' => 1,
    ],
    [
        'unique' => true
    ]
);
```

or via mongo shell:
```js
db.events.createIndex(
    { aggregate_root_id: 1, aggregate_root_version: 1 },
    { unique: true }
)
```
