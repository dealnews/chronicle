<?php

namespace DealNews\Chronicle\Controller;

use DealNews\Chronicle\Service\SessionHandler;
use PageMill\MVC\ControllerAbstract;

/**
 * Base controller for routes that require a logged-in display user.
 *
 * Checks for a valid session before allowing the request to proceed.
 * Redirects to the login page if no session is found.
 *
 * @package DealNews\Chronicle
 */
abstract class AbstractAuthenticated extends ControllerAbstract {

    /**
     * Handles the request, enforcing session authentication before
     * delegating to the standard MVC lifecycle.
     *
     * @return void
     */
    public function handleRequest(): void {
        session_set_save_handler(new SessionHandler(), true);
        session_start();

        if (empty($_SESSION['user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        $this->inputs['user_id'] = $_SESSION['user_id'];

        parent::handleRequest();
    }
}
