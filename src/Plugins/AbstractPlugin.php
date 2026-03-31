<?php

namespace DealNews\Chronicle\Plugins;

use DealNews\GetConfig\GetConfig;

/**
 * Base class for webhook payload plugins.
 *
 * A plugin translates a raw JSON webhook payload into the individual fields
 * required to populate a Log record. Each concrete plugin implements the
 * abstract getter methods for a specific source's payload structure.
 *
 * Plugins are discovered automatically by scanning the Plugins directory.
 * Each concrete class must declare a DESCRIPTION class constant with a
 * human-readable label shown in the admin UI.
 *
 * @package DealNews\Chronicle
 */
abstract class AbstractPlugin {

    /**
     * Decoded payload data.
     *
     * @var array<mixed>
     */
    protected array $payload;

    /**
     * Decodes the raw JSON payload and stores it for use by getter methods.
     *
     * @param  string $payload Raw JSON string from the webhook request body.
     * @param  string $source  Source name as it appears in the route/database.
     *                         Used by config-driven plugins; ignored by others.
     * @param  string $type    Type name as it appears in the route/database.
     *                         Used by config-driven plugins; ignored by others.
     * @throws \InvalidArgumentException If the payload is not valid JSON.
     */
    public function __construct(string $payload, string $source = '', string $type = '') {
        $arr = json_decode($payload, true);

        if (!is_array($arr)) {
            throw new \InvalidArgumentException(
                'Plugin payload must be a valid JSON object. ' .
                'json_decode error: ' . json_last_error_msg()
            );
        }

        $this->payload = $arr;
    }

    /**
     * Discovers all available plugins and returns them as key => description
     * pairs suitable for populating a select element.
     *
     * Built-in plugins are discovered by scanning this directory; their key
     * is the short class name (e.g. "DatoCMS").
     *
     * External plugins are read from the comma-separated config key
     * "chronicle.plugins"; their key is the fully-qualified class name
     * (e.g. "\Foo\Bar\MyPlugin").
     *
     * A class is included when it extends AbstractPlugin and declares a
     * DESCRIPTION constant.
     *
     * @return array<string, string>
     */
    public static function getAvailable(): array {
        $plugins = [];

        // Built-in plugins — scan this directory
        $files = glob(__DIR__ . '/*.php') ?: [];

        foreach ($files as $file) {
            $short_name = basename($file, '.php');

            if ($short_name === 'AbstractPlugin') {
                continue;
            }

            $class = __NAMESPACE__ . '\\' . $short_name;

            if (!is_subclass_of($class, self::class)) {
                continue;
            }

            if (!defined("{$class}::DESCRIPTION")) {
                continue;
            }

            $plugins[$short_name] = constant("{$class}::DESCRIPTION");
        }

        // External plugins — read from config
        $config   = GetConfig::init();
        $external = $config->get('chronicle.plugins');

        if (!empty($external)) {
            $classes = array_map('trim', explode(',', $external));

            foreach ($classes as $class) {
                if (!is_subclass_of($class, self::class)) {
                    continue;
                }

                if (!defined("{$class}::DESCRIPTION")) {
                    continue;
                }

                $plugins[$class] = constant("{$class}::DESCRIPTION");
            }
        }

        ksort($plugins);

        return $plugins;
    }

    /**
     * Resolves a plugin key to a fully-qualified class name.
     *
     * For built-in plugins the key is a short name; the namespace is prepended.
     * For external plugins the key is already a fully-qualified class name.
     * Returns null if the resolved class is not a valid AbstractPlugin subclass.
     *
     * @param  string $key Short name or fully-qualified class name.
     * @return class-string|null
     */
    public static function resolve(string $key): ?string {
        // Try as a built-in short name first
        $built_in = __NAMESPACE__ . '\\' . $key;

        if (is_subclass_of($built_in, self::class)) {
            return $built_in;
        }

        // Try as a fully-qualified external class name
        if (is_subclass_of($key, self::class)) {
            return $key;
        }

        return null;
    }

    /**
     * Returns the object's data to be stored in the log.
     *
     * @return array<mixed>
     */
    abstract public function getData(): array;

    /**
     * Returns the datetime string representing when the change occurred, or
     * null if the payload does not contain a change date. When null is
     * returned, the ingestion layer will fall back to the current UTC time.
     *
     * @return string|null
     */
    abstract public function getChangeDate(): ?string;

    /**
     * Returns an identifier for who made the change, or null if not available.
     *
     * @return string|null
     */
    abstract public function getChangedBy(): ?string;

    /**
     * Returns the source system's unique identifier for the object.
     *
     * @return string
     */
    abstract public function getObjectId(): string;

    /**
     * Returns the canonical action: "create", "update", or "delete".
     * Returns null if the action cannot be determined; the caller will
     * default to "update".
     *
     * @return string|null
     */
    abstract public function getAction(): ?string;

    /**
     * Returns a version identifier for the object state, or null if not available.
     *
     * @return string|null
     */
    abstract public function getVersion(): ?string;
}
