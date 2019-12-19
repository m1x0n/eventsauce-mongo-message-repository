<?php

declare(strict_types=1);

namespace EventSauceExtensions;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use MongoDB\Database;

/**
 * @package EventSauceExtensions
 */
class MongoDbMessageRepositoryFactory
{
    private const DEFAULT_COLLECTION_NAME = 'domain_events';

    /**
     * @param Database $database
     * @param string $collectionName
     * @return MongoDbMessageRepository
     */
    public function create(
        Database $database,
        string $collectionName = self::DEFAULT_COLLECTION_NAME
    ): MongoDbMessageRepository {
        return new MongoDbMessageRepository(
            $database,
            new MongoDbMessageSerializer(
                new ConstructingMessageSerializer()
            ),
            $collectionName
        );
    }
}
