<?php

namespace DealNews\Chronicle\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Value object for chronicle.sources
 *
 * @package DealNews\Chronicle
 */
class Source extends ValueObject {

    /**
     * @var string
     */
    public string $created_at = '';

    /**
     * @var ?string
     */
    public ?string $name = null;

    /**
     * @var ?string
     */
    public ?string $description = null;

    /**
     * @var int
     */
    public int $source_id = 0;

    /**
     * @var ?string
     */
    public ?string $updated_at = null;

}
