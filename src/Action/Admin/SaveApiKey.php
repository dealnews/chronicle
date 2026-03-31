<?php

namespace DealNews\Chronicle\Action\Admin;

use DealNews\Chronicle\Data\ApiKey;
use DealNews\Chronicle\Mapper\ApiKey as ApiKeyMapper;
use DealNews\Chronicle\Action\AbstractCsrfAction;

/**
 * Creates a new API key or revokes an existing one.
 *
 * Create: POST /admin/api-keys with `name` in the body.
 *   Generates a random key, stores its SHA-256 hash, and returns the
 *   plaintext key once in the response data so the view can display it.
 *
 * Revoke: POST /admin/api-keys/{id} sets revoked_at on the key record.
 *
 * @package DealNews\Chronicle
 */
class SaveApiKey extends AbstractCsrfAction {

    /**
     * Human-readable label for the key (create only).
     *
     * @var string
     */
    protected string $name = '';

    /**
     * Key ID parsed from the URL path (> 0 triggers revocation).
     *
     * @var int
     */
    protected int $admin_id = 0;

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    protected function doCsrfAction(array $data = []): mixed {
        $mapper = new ApiKeyMapper();

        if ($this->admin_id > 0) {
            return $this->revokeKey($mapper);
        }

        return $this->createKey($mapper);
    }

    /**
     * Generates a new random API key, stores its hash, and returns
     * the plaintext key for one-time display.
     *
     * @param  ApiKeyMapper $mapper
     * @return array<string, mixed>|null
     */
    protected function createKey(ApiKeyMapper $mapper): ?array {
        if (empty($this->name)) {
            $this->errors[] = 'API key name is required.';
            return null;
        }

        $raw_key  = bin2hex(random_bytes(32));
        $key_hash = hash('sha256', $raw_key);

        $api_key           = new ApiKey();
        $api_key->name     = $this->name;
        $api_key->key_hash = $key_hash;

        $mapper->save($api_key);

        // Return the plaintext key so the view can display it once.
        return ['new_api_key' => $raw_key];
    }

    /**
     * Sets revoked_at on an existing key.
     *
     * @param  ApiKeyMapper $mapper
     * @return null
     */
    protected function revokeKey(ApiKeyMapper $mapper): ?array {
        $api_key = $mapper->load($this->admin_id);

        if ($api_key === null) {
            $this->errors[] = 'API key not found.';
            return null;
        }

        if ($api_key->revoked_at !== null) {
            $this->errors[] = 'API key is already revoked.';
            return null;
        }

        $api_key->revoked_at = date('Y-m-d H:i:s');
        $mapper->save($api_key);

        header('Location: /admin/api-keys');
        exit;
    }
}
