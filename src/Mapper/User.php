<?php

namespace DealNews\Chronicle\Mapper;

use DealNews\Chronicle\Data\User as UserData;
use DealNews\DB\AbstractMapper;

/**
 * Maps Data\User objects to the chronicle.chronicle_users table.
 *
 * @package DealNews\Chronicle
 */
class User extends AbstractMapper {

    /**
     * Database name
     */
    public const DATABASE_NAME = 'chronicle';

    /**
     * Table name
     */
    public const TABLE = 'chronicle_users';

    /**
     * Table primary key column name
     */
    public const PRIMARY_KEY = 'user_id';

    /**
     * Name of the class the mapper is mapping
     */
    public const MAPPED_CLASS = UserData::class;

    /**
     * Defines the properties that are mapped and any
     * additional information needed to map them.
     */
    public const MAPPING = [
        'user_id'       => [],
        'email'         => [],
        'name'          => [],
        'password_hash' => [],
        'google_id'     => [],
        'created_at'    => [
            'read_only' => true,
        ],
        'last_login_at' => [],
    ];
}
