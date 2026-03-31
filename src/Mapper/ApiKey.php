<?php

namespace DealNews\Chronicle\Mapper;

use DealNews\Chronicle\Data\ApiKey as ApiKeyData;
use DealNews\DB\AbstractMapper;

/**
 * Maps Data\ApiKey objects to the chronicle.chronicle_api_keys table.
 *
 * @package DealNews\Chronicle
 */
class ApiKey extends AbstractMapper {

    /**
     * Database name
     */
    public const DATABASE_NAME = 'chronicle';

    /**
     * Table name
     */
    public const TABLE = 'chronicle_api_keys';

    /**
     * Table primary key column name
     */
    public const PRIMARY_KEY = 'api_key_id';

    /**
     * Name of the class the mapper is mapping
     */
    public const MAPPED_CLASS = ApiKeyData::class;

    /**
     * Defines the properties that are mapped and any
     * additional information needed to map them.
     */
    public const MAPPING = [
        'api_key_id' => [],
        'name'       => [],
        'key_hash'   => [],
        'created_at' => [
            'read_only' => true,
        ],
        'revoked_at' => [],
    ];
}
