<?php

namespace DealNews\Chronicle\Model\Admin;

use DealNews\Chronicle\Mapper\ApiKey;
use PageMill\MVC\ModelAbstract;

/**
 * Returns all API keys for the admin API key management page.
 *
 * @package DealNews\Chronicle
 */
class ApiKeyList extends ModelAbstract {

    /**
     * @return array<string, mixed>
     */
    public function getData(): array {
        $mapper   = new ApiKey();
        $api_keys = $mapper->find([], order: 'created_at DESC') ?? [];

        return ['api_keys' => array_values($api_keys)];
    }
}
