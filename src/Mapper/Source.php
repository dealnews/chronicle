<?php

namespace DealNews\Chronicle\Mapper;

use DealNews\Chronicle\Data\Source as SourceData;
use DealNews\DB\AbstractMapper;

/**
 * Maps Data\Source objects to the chronicle.sources table.
 *
 * @package DealNews\Chronicle
 */
class Source extends AbstractMapper {

    /**
     * Database name
     */
    public const DATABASE_NAME = 'chronicle';

    /**
     * Table name
     */
    public const TABLE = 'sources';

    /**
     * Table primary key column name
     */
    public const PRIMARY_KEY = 'source_id';

    /**
     * Name of the class the mapper is mapping
     */
    public const MAPPED_CLASS = SourceData::class;

    /**
     * Defines the properties that are mapped and any
     * additional information needed to map them.
     */
    public const MAPPING = [
        'created_at' => [
            'read_only' => true,
        ],
        'name'       => [],
        'source_id'  => [],
        'updated_at' => [
            'read_only' => true,
        ],
    ];
}
