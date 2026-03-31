<?php

namespace DealNews\Chronicle\Mapper;

use DealNews\Chronicle\Data\Log as LogData;
use DealNews\DB\AbstractMapper;

/**
 * Maps Data\Log objects to the chronicle.logs table.
 *
 * @package DealNews\Chronicle
 */
class Log extends AbstractMapper {

    /**
     * Database name
     */
    public const DATABASE_NAME = 'chronicle';

    /**
     * Table name
     */
    public const TABLE = 'logs';

    /**
     * Table primary key column name
     */
    public const PRIMARY_KEY = 'log_id';

    /**
     * Name of the class the mapper is mapping
     */
    public const MAPPED_CLASS = LogData::class;

    /**
     * Defines the properties that are mapped and any
     * additional information needed to map them.
     */
    public const MAPPING = [
        'action'      => [],
        'change_date' => [],
        'created_at'  => [
            'read_only' => true,
        ],
        'data'        => [],
        'log_id'      => [],
        'object_id'   => [],
        'type_id'     => [],
        'updated_by'  => [],
        'version'     => [],
    ];
}
