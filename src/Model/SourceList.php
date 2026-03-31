<?php

namespace DealNews\Chronicle\Model;

use DealNews\Chronicle\Mapper\Source;
use PageMill\MVC\ModelAbstract;

/**
 * Returns all configured sources for the history index page.
 *
 * @package DealNews\Chronicle
 */
class SourceList extends ModelAbstract {

    /**
     * @return array<string, mixed>
     */
    public function getData(): array {
        $mapper  = new Source();
        $sources = $mapper->find([], order: 'name ASC') ?? [];

        return ['sources' => array_values($sources)];
    }
}
