<?php

namespace DealNews\Chronicle\Model;

use DealNews\Chronicle\Mapper\Source as SourceMapper;
use DealNews\Chronicle\Mapper\Type as TypeMapper;
use PageMill\MVC\ModelAbstract;

/**
 * Looks up the source and type for the object-lookup page.
 *
 * @package DealNews\Chronicle
 */
class LogList extends ModelAbstract {

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
     * Looks up the source and type. Returns null for either if not found.
     *
     * @return array<string, mixed>
     */
    public function getData(): array {
        $source_mapper = new SourceMapper();
        $sources       = $source_mapper->find(['name' => $this->source]);

        if (empty($sources)) {
            return ['source' => null, 'type' => null];
        }

        $source = reset($sources);

        $type_mapper = new TypeMapper();
        $types       = $type_mapper->find([
            'source_id' => $source->source_id,
            'name'      => $this->type,
        ]);

        if (empty($types)) {
            return ['source' => $source, 'type' => null];
        }

        return [
            'source' => $source,
            'type'   => reset($types),
        ];
    }
}
