<?php

namespace DealNews\Chronicle\Model\Admin;

use DealNews\Chronicle\Mapper\Source as SourceMapper;
use DealNews\Chronicle\Mapper\Type as TypeMapper;
use PageMill\MVC\ModelAbstract;

/**
 * Returns all types with their parent sources for the admin types page.
 *
 * Both collections are provided so the view can render the type list
 * and a create form with a source selector.
 *
 * @package DealNews\Chronicle
 */
class TypeList extends ModelAbstract {

    /**
     * Type ID from the URL path. When > 0, the corresponding type is loaded
     * and returned as edit_type for the edit form.
     *
     * @var int
     */
    protected int $admin_id = 0;

    /**
     * @return array<string, mixed>
     */
    public function getData(): array {
        $source_mapper = new SourceMapper();
        $sources       = $source_mapper->find([], order: 'name ASC') ?? [];

        $type_mapper = new TypeMapper();
        $types       = $type_mapper->find([], order: 'name ASC') ?? [];

        $result = [
            'sources'   => array_values($sources),
            'types'     => array_values($types),
            'edit_type' => null,
        ];

        if ($this->admin_id > 0) {
            $result['edit_type'] = $type_mapper->load($this->admin_id);
        }

        return $result;
    }
}
