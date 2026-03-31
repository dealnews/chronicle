<?php

namespace DealNews\Chronicle\Model\Admin;

use DealNews\Chronicle\Mapper\User;
use PageMill\MVC\ModelAbstract;

/**
 * Returns all users for the admin user management page.
 *
 * @package DealNews\Chronicle
 */
class UserList extends ModelAbstract {

    /**
     * User ID from the URL path. When > 0, the corresponding user is
     * loaded and returned as edit_user for the edit form.
     *
     * @var int
     */
    protected int $admin_id = 0;

    /**
     * @return array<string, mixed>
     */
    public function getData(): array {
        $mapper = new User();
        $users  = $mapper->find([], order: 'email ASC') ?? [];

        $result = [
            'users'     => array_values($users),
            'edit_user' => null,
        ];

        if ($this->admin_id > 0) {
            $result['edit_user'] = $mapper->load($this->admin_id);
        }

        return $result;
    }
}
