<?php

namespace DealNews\Chronicle\Plugins;

/**
 * Plugin for DatoCMS webhook payloads.
 *
 * Translates DatoCMS's webhook envelope into Chronicle log fields.
 * Expected payload structure:
 *
 * ```json
 * {
 *   "event_type": "create",
 *   "event_triggered_at": "2024-01-01T12:00:00Z",
 *   "entity": {
 *     "id": "abc123",
 *     "meta": { "current_version": "v1" },
 *     ...
 *   }
 * }
 * ```
 *
 * DatoCMS does not include actor information in its webhook payload, so
 * getChangedBy() always returns null.
 *
 * @package DealNews\Chronicle
 */
class DatoCMS extends AbstractPlugin {

    /**
     * Human-readable label shown in the admin UI plugin selector.
     */
    public const DESCRIPTION = 'DatoCMS';

    /**
     * Returns the full entity object from the payload.
     *
     * @return array<mixed>
     */
    public function getData(): array {
        return $this->payload['entity'];
    }

    /**
     * Returns the event timestamp from the payload.
     *
     * @return string|null
     */
    public function getChangeDate(): ?string {
        return $this->payload['event_triggered_at'] ?? null;
    }

    /**
     * DatoCMS webhooks do not carry actor information.
     *
     * @return null
     */
    public function getChangedBy(): ?string {
        return null;
    }

    /**
     * Returns the entity ID from the payload.
     *
     * @return string
     */
    public function getObjectId(): string {
        return $this->payload['entity']['id'];
    }

    /**
     * Maps DatoCMS event types to canonical Chronicle actions.
     * Any event type other than "create" or "delete" is treated as "update".
     *
     * @return string
     */
    public function getAction(): ?string {
        return match ($this->payload['event_type']) {
            'create' => 'create',
            'delete' => 'delete',
            default  => 'update',
        };
    }

    /**
     * Returns the current version identifier from the entity metadata.
     *
     * @return string|null
     */
    public function getVersion(): ?string {
        return $this->payload['entity']['meta']['current_version'] ?? null;
    }
}
