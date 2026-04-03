<?php

namespace DealNews\Chronicle\Plugins;

use DealNews\GetConfig\GetConfig;
use Symfony\Component\JsonPath\JsonCrawler;

/**
 * Config-driven plugin that uses JSONPath expressions defined in config.ini
 * to extract log fields from any JSON webhook payload.
 *
 * Config keys are scoped to source and type:
 *
 *   chronicle.plugin.{source}.{type}.object_id      = $.entity.id
 *   chronicle.plugin.{source}.{type}.action         = $.event_type
 *   chronicle.plugin.{source}.{type}.change_date    = $.event_triggered_at
 *   chronicle.plugin.{source}.{type}.version        = $.entity.meta.current_version
 *   chronicle.plugin.{source}.{type}.data           = $.entity
 *   chronicle.plugin.{source}.{type}.changed_by     = $.editor.email
 *
 *   ; Comma-separated raw values that map to each canonical action
 *   chronicle.plugin.{source}.{type}.create_actions = create,publish
 *   chronicle.plugin.{source}.{type}.update_actions = update,save
 *   chronicle.plugin.{source}.{type}.delete_actions = delete,unpublish,archive
 *
 * object_id, action, and change_date are required — an exception is thrown
 * if their config keys are absent or their JSONPath yields no result.
 * version, data, and changed_by are optional — null is returned when not set.
 *
 * @package DealNews\Chronicle
 */
class JsonPath extends AbstractPlugin {

    /**
     * Human-readable label shown in the admin UI plugin selector.
     */
    public const DESCRIPTION = 'JSONPath (config-driven)';

    /**
     * Config key prefix for this source/type combination.
     *
     * @var string
     */
    protected string $config_prefix = '';

    /**
     * Loaded config instance.
     *
     * @var GetConfig
     */
    protected GetConfig $config;

    /**
     * @param string          $payload Raw JSON webhook body.
     * @param string          $source  Source name as it appears in the route/database.
     * @param string          $type    Type name as it appears in the route/database.
     * @param GetConfig|null  $config  Config instance; defaults to the singleton.
     */
    public function __construct(
        string $payload,
        string $source = '',
        string $type = '',
        ?GetConfig $config = null
    ) {
        parent::__construct($payload, $source, $type);

        $this->config        = $config ?? \DealNews\GetConfig\GetConfig::init();
        $this->config_prefix = "chronicle.plugin.{$source}.{$type}";
    }

    /**
     * Returns the object's data as an array, extracted via the configured
     * JSONPath expression. Returns the full payload if no path is configured.
     *
     * @return array<mixed>
     */
    public function getData(): array {
        $path = $this->config->get("{$this->config_prefix}.data");

        if (empty($path)) {
            return $this->payload;
        }

        $result = $this->query($path);

        if (is_array($result)) {
            return $result;
        }

        return [$result];
    }

    /**
     * @return string|null
     * @throws \RuntimeException If the config key or JSONPath result is missing.
     */
    public function getChangeDate(): ?string {
        return (string) $this->required('change_date') ?: null;
    }

    /**
     * @return string|null
     */
    public function getChangedBy(): ?string {
        $path = $this->config->get("{$this->config_prefix}.changed_by");

        if (empty($path)) {
            return null;
        }

        $result = $this->query($path);

        return $result !== null ? (string) $result : null;
    }

    /**
     * @return string
     * @throws \RuntimeException If the config key or JSONPath result is missing.
     */
    public function getObjectId(): string {
        return (string) $this->required('object_id');
    }

    /**
     * Evaluates the configured action JSONPath, then maps the raw value to
     * a canonical action (create/update/delete) using the *_actions config keys.
     * Returns the raw value unchanged if no mapping keys are configured.
     *
     * @return string|null
     * @throws \RuntimeException If the config key or JSONPath result is missing.
     */
    public function getAction(): ?string {
        $raw = (string) $this->required('action');

        foreach (['create', 'update', 'delete'] as $canonical) {
            $map_key = "{$this->config_prefix}.{$canonical}_actions";
            $values  = $this->config->get($map_key);

            if (empty($values)) {
                continue;
            }

            $list = array_map('trim', explode(',', $values));

            if (in_array($raw, $list, true)) {
                return $canonical;
            }
        }

        return $raw;
    }

    /**
     * @return string|null
     */
    public function getVersion(): ?string {
        $path = $this->config->get("{$this->config_prefix}.version");

        if (empty($path)) {
            return null;
        }

        $result = $this->query($path);

        return $result !== null ? (string) $result : null;
    }

    /**
     * Looks up a required config key, evaluates its JSONPath expression,
     * and returns the scalar result.
     *
     * @param  string $key  Short config key (e.g. "object_id").
     * @return mixed
     * @throws \RuntimeException If the key is not configured or yields no result.
     */
    protected function required(string $key): mixed {
        $path = $this->config->get("{$this->config_prefix}.{$key}");

        if (empty($path)) {
            throw new \RuntimeException(
                "JsonPath plugin: required config key " .
                "'{$this->config_prefix}.{$key}' is not set."
            );
        }

        $result = $this->query($path);

        if ($result === null) {
            throw new \RuntimeException(
                "JsonPath plugin: JSONPath expression '{$path}' for key " .
                "'{$key}' returned no result."
            );
        }

        return $result;
    }

    /**
     * Evaluates a JSONPath expression against the payload and returns the
     * first result, or null if nothing matched.
     *
     * @param  string $path JSONPath expression (e.g. "$.entity.id").
     * @return mixed
     */
    protected function query(string $path): mixed {
        $crawler = new JsonCrawler(json_encode($this->payload));
        $results = $crawler->find($path);

        if (empty($results)) {
            return null;
        }

        return $results[0];
    }
}
