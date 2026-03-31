<?php

namespace DealNews\Chronicle\Controller;

use DealNews\Chronicle\Action\CreateFirstUser;
use DealNews\Chronicle\Action\GoogleOAuthCallback;
use DealNews\Chronicle\Action\PasswordLogin;
use DealNews\Chronicle\Model\UserCount;
use DealNews\Chronicle\Responder\Auth as AuthResponder;
use DealNews\Chronicle\Service\SessionHandler;
use PageMill\MVC\ControllerAbstract;
use PageMill\MVC\ResponderAbstract;

/**
 * Handles Google OAuth login and callback routes.
 *
 * Routes:
 *   GET /auth/login    - Redirect the user to Google for authentication.
 *   GET /auth/callback - Handle the OAuth callback from Google.
 *
 * @package DealNews\Chronicle
 */
class Auth extends ControllerAbstract {

    /**
     * Starts the session and captures fields for both the login form
     * (POST) and the OAuth callback (GET).
     *
     * @param  array<int, array<string, mixed>> $filters
     * @return void
     */
    protected function filterInput(array $filters): void {
        session_set_save_handler(new SessionHandler(), true);
        session_start();

        parent::filterInput([
            INPUT_GET => [
                'code'  => FILTER_SANITIZE_ENCODED,
                'state' => FILTER_SANITIZE_ENCODED,
            ],
            INPUT_POST => [
                'name'        => FILTER_DEFAULT,
                'email'       => FILTER_SANITIZE_EMAIL,
                'password'    => FILTER_DEFAULT,
                '_csrf_token' => FILTER_DEFAULT,
            ],
        ]);
    }

    /**
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new AuthResponder();
    }

    /**
     * Routes to the appropriate action based on path and request method.
     * GET /auth/login has no action — the view renders the login form.
     *
     * @return array<int, class-string>
     */
    protected function getRequestActions(): array {
        if ($this->request_path === '/auth/callback') {
            return [GoogleOAuthCallback::class];
        }

        if ($this->request_path === '/auth/setup') {
            return [CreateFirstUser::class];
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            return [PasswordLogin::class];
        }

        return [];
    }

    /**
     * @return array<int, class-string>
     */
    protected function getModels(): array {
        return [UserCount::class];
    }
}
