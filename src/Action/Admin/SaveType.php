<?php

namespace DealNews\Chronicle\Action\Admin;

use DealNews\Chronicle\Data\Type;
use DealNews\Chronicle\Mapper\Type as TypeMapper;
use DealNews\Chronicle\Plugins\AbstractPlugin;
use DealNews\Chronicle\Action\AbstractCsrfAction;

/**
 * Creates, updates, or deletes a type.
 *
 *   POST   /admin/types           admin_id == 0  → create
 *   POST   /admin/types/{id}      admin_id  > 0  → update
 *   POST   /admin/types/{id}      admin_id  > 0, _delete == 1 → delete
 *
 * @package DealNews\Chronicle
 */
class SaveType extends AbstractCsrfAction {

    /**
     * Type name from POST body.
     *
     * @var string
     */
    protected string $name = '';

    /**
     * Short plugin class name from POST body.
     *
     * @var string
     */
    protected string $plugin = '';

    /**
     * Parent source ID from POST body.
     *
     * @var int
     */
    protected int $source_id = 0;

    /**
     * Type ID parsed from the URL path (> 0 triggers update or delete).
     *
     * @var int
     */
    protected int $admin_id = 0;

    /**
     * When "1", deletes the type instead of saving it.
     *
     * @var string|null
     */
    protected ?string $_delete = null;

    /**
     * @param  array<string, mixed> $data
     * @return null
     */
    protected function doCsrfAction(array $data = []): mixed {
        $mapper = new TypeMapper();

        if ($this->admin_id > 0 && $this->_delete !== null) {
            return $this->deleteType($mapper);
        }

        if ($this->admin_id > 0) {
            return $this->updateType($mapper);
        }

        return $this->createType($mapper);
    }

    /**
     * Creates a new type.
     *
     * @param  TypeMapper $mapper
     * @return null
     */
    protected function createType(TypeMapper $mapper): mixed {
        if (!$this->validate()) {
            return null;
        }

        $type            = new Type();
        $type->name      = $this->name;
        $type->plugin    = $this->plugin;
        $type->source_id = $this->source_id;

        $mapper->save($type);

        header('Location: /admin/types');
        exit;
    }

    /**
     * Updates an existing type.
     *
     * @param  TypeMapper $mapper
     * @return null
     */
    protected function updateType(TypeMapper $mapper): mixed {
        if (!$this->validate()) {
            return null;
        }

        $type = $mapper->load($this->admin_id);

        if ($type === null) {
            $this->errors[] = 'Type not found.';
            return null;
        }

        $type->name      = $this->name;
        $type->plugin    = $this->plugin;
        $type->source_id = $this->source_id;

        $mapper->save($type);

        header('Location: /admin/types');
        exit;
    }

    /**
     * Deletes a type.
     *
     * @param  TypeMapper $mapper
     * @return null
     */
    protected function deleteType(TypeMapper $mapper): mixed {
        $type = $mapper->load($this->admin_id);

        if ($type === null) {
            $this->errors[] = 'Type not found.';
            return null;
        }

        $mapper->delete($type);

        header('Location: /admin/types');
        exit;
    }

    /**
     * Validates name, source_id, and plugin. Adds errors and returns false on failure.
     *
     * @return bool
     */
    protected function validate(): bool {
        $valid = true;

        if (empty($this->name)) {
            $this->errors[] = 'Type name is required.';
            $valid = false;
        }

        if ($this->source_id === 0) {
            $this->errors[] = 'A source is required.';
            $valid = false;
        }

        if (empty($this->plugin)) {
            $this->errors[] = 'A plugin is required.';
            $valid = false;
        } elseif (AbstractPlugin::resolve($this->plugin) === null) {
            $this->errors[] = 'Unknown plugin: ' . $this->plugin . '.';
            $valid = false;
        }

        return $valid;
    }
}
