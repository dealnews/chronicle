<?php

namespace DealNews\Chronicle\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Value object for chronicle.types
 *
 * @package DealNews\Chronicle
 */
class Type extends ValueObject {

    /**
     * @var string
     */
    public string $created_at = '';

    /**
     * @var string
     */
    public string $name = '';

    /**
     * Short class name of the plugin used to parse incoming payloads,
     * or null if no plugin is configured.
     *
     * @var ?string
     */
    public ?string $plugin = null;

    /**
     * @var int
     */
    public int $source_id = 0;

    /**
     * @var int
     */
    public int $type_id = 0;

    /**
     * @var ?string
     */
    public ?string $updated_at = null;

}
