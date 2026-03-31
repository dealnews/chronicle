<?php

namespace DealNews\Chronicle\Mapper;

use DealNews\Chronicle\Data\Type as TypeData;
use DealNews\DB\AbstractMapper;

/**
 * Maps Data\Type objects to the chronicle.types table.
 *
 * @package DealNews\Chronicle
 */
class Type extends AbstractMapper {

    /**
     * Database name
     */
    public const DATABASE_NAME = 'chronicle';

    /**
     * Table name
     */
    public const TABLE = 'types';

    /**
     * Table primary key column name
     */
    public const PRIMARY_KEY = 'type_id';

    /**
     * Name of the class the mapper is mapping
     */
    public const MAPPED_CLASS = TypeData::class;

    /**
     * Defines the properties that are mapped and any
     * additional information needed to map them.
     */
    public const MAPPING = [
        'created_at' => [
            'read_only' => true,
        ],
        'name'        => [],
        'description' => [],
        'plugin'      => [],
        'source_id'  => [],
        'type_id'    => [],
        'updated_at' => [
            'read_only' => true,
        ],
    ];
}
