<?php

namespace DealNews\Chronicle\Model;

use DealNews\Chronicle\Mapper\Source as SourceMapper;
use DealNews\Chronicle\Mapper\Type as TypeMapper;
use PageMill\MVC\ModelAbstract;

/**
 * Returns a source and its associated types.
 *
 * @package DealNews\Chronicle
 */
class TypeList extends ModelAbstract {

    /**
     * Source name from route token.
     *
     * @var string
     */
    protected string $source = '';

    /**
     * Looks up the source by name, then retrieves its types.
     * Returns an empty array if the source is not found.
     *
     * @return array<string, mixed>
     */
    public function getData(): array {
        $source_mapper = new SourceMapper();
        $sources       = $source_mapper->find(['name' => $this->source]);

        if (empty($sources)) {
            return ['source' => null, 'types' => []];
        }

        $source = reset($sources);

        $type_mapper = new TypeMapper();
        $types       = $type_mapper->find(
            ['source_id' => $source->source_id],
            order: 'name ASC'
        ) ?? [];

        return [
            'source' => $source,
            'types'  => array_values($types),
        ];
    }
}
