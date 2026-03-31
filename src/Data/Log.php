<?php

namespace DealNews\Chronicle\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Value object for chronicle.logs
 *
 * @package DealNews\Chronicle
 */
class Log extends ValueObject {

    /**
     * @var string
     */
    public string $action = 'create';

    /**
     * @var string
     */
    public string $change_date = '';

    /**
     * @var string
     */
    public string $created_at = '';

    /**
     * @var ?string
     */
    public ?string $data = null;

    /**
     * @var int
     */
    public int $log_id = 0;

    /**
     * @var string
     */
    public string $object_id = '';

    /**
     * @var int
     */
    public int $type_id = 0;

    /**
     * @var ?string
     */
    public ?string $updated_by = null;

    /**
     * @var ?string
     */
    public ?string $version = null;

}
