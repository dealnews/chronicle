<?php

namespace DealNews\Chronicle\Action;

use DateTimeImmutable;
use DateTimeZone;
use DealNews\Chronicle\Data\ApiKey;
use DealNews\Chronicle\Data\Log;
use DealNews\Chronicle\Data\Source;
use DealNews\Chronicle\Data\Type;
use DealNews\Chronicle\Mapper\ApiKey as ApiKeyMapper;
use DealNews\Chronicle\Mapper\Log as LogMapper;
use DealNews\Chronicle\Mapper\Source as SourceMapper;
use DealNews\Chronicle\Mapper\Type as TypeMapper;
use DealNews\Chronicle\Plugins\AbstractPlugin;
use InvalidArgumentException;
use PageMill\MVC\ActionAbstract;
use RuntimeException;
use Throwable;

/**
 * Validates the incoming webhook request and persists a log entry.
 *
 * Runs as a request action on POST /webhook/{source}/{type}.
 *
 * Validation order:
 *   1. API key present and not revoked (401)
 *   2. Source and type are configured (404)
 *   3. Type has a plugin configured (400)
 *   4. Plugin resolves to a known class (500)
 *   5. Body is non-empty and valid JSON (400)
 *   6. Plugin extracts a parseable change_date (400)
 *
 * On success, returns ['http_status' => 200, 'log_id' => int].
 * On failure, adds to $errors and returns ['http_status' => int].
 *
 * @package DealNews\Chronicle
 */
class IngestWebhook extends ActionAbstract {

    /**
     * Raw Authorization header value, e.g. "Bearer abc123".
     *
     * @var string
     */
    protected string $authorization = '';

    /**
     * Raw JSON request body.
     *
     * @var string
     */
    protected string $body = '';

    /**
     * Source name from route token.
     *
     * @var string
     */
    protected string $source = '';

    /**
     * Type name from route token.
     *
     * @var string
     */
    protected string $type = '';

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function doAction(array $data = []): mixed {
        $api_key = $this->validateApiKey();

        if ($api_key === null) {
            $this->errors[] = 'Invalid or revoked API key.';
            return ['http_status' => 401];
        }

        $source = $this->findSource();

        if ($source === null) {
            $this->errors[] = "Source '{$this->source}' is not configured.";
            return ['http_status' => 404];
        }

        $type = $this->findType($source->source_id);

        if ($type === null) {
            $this->errors[] = "Type '{$this->type}' is not configured for source '{$this->source}'.";
            return ['http_status' => 404];
        }

        $log          = new Log();
        $log->type_id = $type->type_id;

        if ($type->plugin === null) {
            $this->errors[] = "No plugin configured for type '{$this->type}'. Assign a plugin in the admin UI.";
            return ['http_status' => 400];
        }

        $class = AbstractPlugin::resolve($type->plugin);

        if ($class === null) {
            $this->errors[] = "Configured plugin '{$type->plugin}' could not be resolved.";
            return ['http_status' => 500];
        }

        if (empty($this->body)) {
            $this->errors[] = 'Request body is empty.';
            return ['http_status' => 400];
        }

        try {
            $plugin = new $class($this->body, $this->source, $this->type);
        } catch (InvalidArgumentException $e) {
            $this->errors[] = 'Invalid webhook payload: ' . $e->getMessage();
            return ['http_status' => 400];
        } catch (RuntimeException $e) {
            $this->errors[] = 'Plugin configuration error: ' . $e->getMessage();
            return ['http_status' => 500];
        }

        $change_date = $this->normalizeDate($plugin->getChangeDate())
            ?? (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $log->action      = $plugin->getAction() ?? 'update';
        $log->object_id   = $plugin->getObjectId();
        $log->change_date = $change_date;
        $log->version     = $plugin->getVersion();
        $log->data        = json_encode($plugin->getData());
        $log->updated_by  = $plugin->getChangedBy();

        $mapper = new LogMapper();
        $log    = $mapper->save($log);

        return ['http_status' => 201, 'log_id' => $log->log_id];
    }

    /**
     * Normalizes a date string to the Y-m-d H:i:s format expected by the
     * database, always stored in UTC. Returns null if the value is null or
     * cannot be parsed.
     *
     * @param  string|null $value Raw date string from the payload or plugin.
     * @return string|null
     */
    protected function normalizeDate(?string $value): ?string {
        if ($value === null) {
            return null;
        }
        try {
            $dt = new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }

        return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * Extracts the Bearer token from the Authorization header, hashes it,
     * and looks it up in the database.
     *
     * @return ApiKey|null
     */
    protected function validateApiKey(): ?ApiKey {
        if (!str_starts_with($this->authorization, 'Bearer ')) {
            return null;
        }

        $raw_key  = substr($this->authorization, 7);
        $key_hash = hash('sha256', $raw_key);

        $mapper   = new ApiKeyMapper();
        $api_keys = $mapper->find(['key_hash' => $key_hash]);

        if (empty($api_keys)) {
            return null;
        }

        $api_key = reset($api_keys);

        if ($api_key->revoked_at !== null) {
            return null;
        }

        return $api_key;
    }

    /**
     * Finds a source by name.
     *
     * @return Source|null
     */
    protected function findSource(): ?Source {
        $mapper  = new SourceMapper();
        $sources = $mapper->find(['name' => $this->source]);

        if (empty($sources)) {
            return null;
        }

        return reset($sources);
    }

    /**
     * Finds a type by source ID and name.
     *
     * @param  int $source_id
     * @return Type|null
     */
    protected function findType(int $source_id): ?Type {
        $mapper = new TypeMapper();
        $types  = $mapper->find([
            'source_id' => $source_id,
            'name'      => $this->type,
        ]);

        if (empty($types)) {
            return null;
        }

        return reset($types);
    }
}
