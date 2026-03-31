<?php

namespace DealNews\Chronicle\Model;

use DealNews\DB\CRUD;
use PageMill\MVC\ModelAbstract;

/**
 * Returns the total number of users registered in the system.
 *
 * Used by the Auth controller to determine whether to show the first-run
 * setup form or the normal login form.
 *
 * @package DealNews\Chronicle
 */
class UserCount extends ModelAbstract {

    /**
     * @return array<string, mixed>
     */
    public function getData(): array {
        $crud  = CRUD::factory('chronicle');
        $sth   = $crud->run('SELECT COUNT(*) FROM users');
        $count = (int) $sth->fetchColumn();

        return ['user_count' => $count];
    }
}
