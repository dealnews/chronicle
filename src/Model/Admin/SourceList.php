<?php

namespace DealNews\Chronicle\Model\Admin;

use DealNews\Chronicle\Mapper\Source;
use PageMill\MVC\ModelAbstract;

/**
 * Returns all sources for the admin sources management page.
 *
 * @package DealNews\Chronicle
 */
class SourceList extends ModelAbstract {

    /**
     * Source ID from the URL path. When > 0, the corresponding source is
     * loaded and returned as edit_source for the edit form.
     *
     * @var int
     */
    protected int $admin_id = 0;

    /**
     * @return array<string, mixed>
     */
    public function getData(): array {
        $mapper  = new Source();
        $sources = $mapper->find([], order: 'name ASC') ?? [];

        $result = [
            'sources'     => array_values($sources),
            'edit_source' => null,
        ];

        if ($this->admin_id > 0) {
            $result['edit_source'] = $mapper->load($this->admin_id);
        }

        return $result;
    }
}
