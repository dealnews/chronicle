<?php

namespace DealNews\Chronicle\Model;

use DealNews\Chronicle\Mapper\Log as LogMapper;
use DealNews\Chronicle\Mapper\Source as SourceMapper;
use DealNews\Chronicle\Mapper\Type as TypeMapper;
use PageMill\MVC\ModelAbstract;

/**
 * Returns all log versions for a specific object, ordered chronologically
 * with any "create" event forced to the front.
 *
 * The view is responsible for diffing consecutive versions.
 *
 * @package DealNews\Chronicle
 */
class LogDetail extends ModelAbstract {

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
     * Object ID from route token.
     *
     * @var string
     */
    protected string $object_id = '';

    /**
     * Looks up the source, type, and all log entries for the object.
     * Returns an empty array if the source or type is not found.
     *
     * @return array<string, mixed>
     */
    public function getData(): array {
        $source_mapper = new SourceMapper();
        $sources       = $source_mapper->find(['name' => $this->source]);

        if (empty($sources)) {
            return ['source' => null, 'type' => null, 'logs' => []];
        }

        $source = reset($sources);

        $type_mapper = new TypeMapper();
        $types       = $type_mapper->find([
            'source_id' => $source->source_id,
            'name'      => $this->type,
        ]);

        if (empty($types)) {
            return ['source' => $source, 'type' => null, 'logs' => []];
        }

        $type = reset($types);

        $log_mapper = new LogMapper();
        $logs       = $log_mapper->find(
            [
                'type_id'   => $type->type_id,
                'object_id' => $this->object_id,
            ],
            order: 'change_date ASC'
        ) ?? [];

        // Some systems send a create and update event simultaneously.
        // Ensure any "create" entry sorts before all others so it
        // always appears as the initial version when diffing.
        usort($logs, function ($a, $b) {
            $a_create = $a->action === 'create' ? 0 : 1;
            $b_create = $b->action === 'create' ? 0 : 1;
            return $a_create <=> $b_create;
        });

        return [
            'source'    => $source,
            'type'      => $type,
            'object_id' => $this->object_id,
            'logs'      => array_values($logs),
        ];
    }
}
