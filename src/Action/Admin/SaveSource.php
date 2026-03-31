<?php

namespace DealNews\Chronicle\Action\Admin;

use DealNews\Chronicle\Data\Source;
use DealNews\Chronicle\Mapper\Source as SourceMapper;
use DealNews\Chronicle\Action\AbstractCsrfAction;

/**
 * Creates, updates, or deletes a source.
 *
 *   POST /admin/sources        admin_id == 0 → create
 *   POST /admin/sources/{id}   admin_id  > 0 → update
 *   POST /admin/sources/{id}   admin_id  > 0, _delete set → delete
 *
 * @package DealNews\Chronicle
 */
class SaveSource extends AbstractCsrfAction {

    /**
     * Source name from POST body.
     *
     * @var string
     */
    protected string $name = '';

    /**
     * Source ID parsed from the URL path (> 0 triggers update or delete).
     *
     * @var int
     */
    protected int $admin_id = 0;

    /**
     * When set, deletes the source instead of saving it.
     *
     * @var string|null
     */
    protected ?string $_delete = null;

    /**
     * @param  array<string, mixed> $data
     * @return null
     */
    protected function doCsrfAction(array $data = []): mixed {
        $mapper = new SourceMapper();

        if ($this->admin_id > 0 && $this->_delete !== null) {
            return $this->deleteSource($mapper);
        }

        if ($this->admin_id > 0) {
            return $this->updateSource($mapper);
        }

        return $this->createSource($mapper);
    }

    /**
     * @param  SourceMapper $mapper
     * @return null
     */
    protected function createSource(SourceMapper $mapper): mixed {
        if (empty($this->name)) {
            $this->errors[] = 'Source name is required.';
            return null;
        }

        $source       = new Source();
        $source->name = $this->name;

        $mapper->save($source);

        header('Location: /admin/sources');
        exit;
    }

    /**
     * @param  SourceMapper $mapper
     * @return null
     */
    protected function updateSource(SourceMapper $mapper): mixed {
        if (empty($this->name)) {
            $this->errors[] = 'Source name is required.';
            return null;
        }

        $source = $mapper->load($this->admin_id);

        if ($source === null) {
            $this->errors[] = 'Source not found.';
            return null;
        }

        $source->name = $this->name;
        $mapper->save($source);

        header('Location: /admin/sources');
        exit;
    }

    /**
     * @param  SourceMapper $mapper
     * @return null
     */
    protected function deleteSource(SourceMapper $mapper): mixed {
        $source = $mapper->load($this->admin_id);

        if ($source === null) {
            $this->errors[] = 'Source not found.';
            return null;
        }

        $mapper->delete($source);

        header('Location: /admin/sources');
        exit;
    }
}
