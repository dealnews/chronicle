<?php

namespace DealNews\Chronicle\Action;

use PageMill\MVC\ActionAbstract;

/**
 * Destroys the current session and redirects to the login page.
 *
 * @package DealNews\Chronicle
 */
class Logout extends ActionAbstract {

    /**
     * @param  array<string, mixed> $data
     * @return null
     */
    public function doAction(array $data = []): mixed {
        $_SESSION = [];
        session_destroy();

        header('Location: /auth/login');
        exit;
    }
}
