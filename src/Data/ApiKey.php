<?php

namespace DealNews\Chronicle\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Value object for chronicle.api_keys
 *
 * @package DealNews\Chronicle
 */
class ApiKey extends ValueObject {

    /**
     * @var int
     */
    public int $api_key_id = 0;

    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var string
     */
    public string $key_hash = '';

    /**
     * @var string
     */
    public string $created_at = '';

    /**
     * @var ?string
     */
    public ?string $revoked_at = null;

}
