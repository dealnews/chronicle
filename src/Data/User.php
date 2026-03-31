<?php

namespace DealNews\Chronicle\Data;

use Moonspot\ValueObjects\ValueObject;

/**
 * Value object for chronicle.users
 *
 * @package DealNews\Chronicle
 */
class User extends ValueObject {

    /**
     * @var int
     */
    public int $user_id = 0;

    /**
     * @var string
     */
    public string $email = '';

    /**
     * @var ?string
     */
    public ?string $name = null;

    /**
     * @var ?string
     */
    public ?string $password_hash = null;

    /**
     * @var ?string
     */
    public ?string $google_id = null;

    /**
     * @var string
     */
    public string $created_at = '';

    /**
     * @var ?string
     */
    public ?string $last_login_at = null;

}
