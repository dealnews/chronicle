<?php

namespace DealNews\Chronicle\Action;

use DealNews\Chronicle\Mapper\User;

/**
 * Authenticates a user with email and password.
 *
 * On success, writes the user_id to the session and redirects to /.
 * On failure, adds an error so the login view can display it.
 *
 * @package DealNews\Chronicle
 */
class PasswordLogin extends AbstractCsrfAction {

    /**
     * Email address from the login form.
     *
     * @var string
     */
    protected string $email = '';

    /**
     * Password from the login form.
     *
     * @var string
     */
    protected string $password = '';

    /**
     * @param  array<string, mixed> $data
     * @return null
     */
    protected function doCsrfAction(array $data = []): mixed {
        if (empty($this->email) || empty($this->password)) {
            $this->errors[] = 'Email and password are required.';
            return null;
        }

        $mapper = new User();
        $users  = $mapper->find(['email' => $this->email]);

        if (empty($users)) {
            $this->errors[] = 'Invalid email or password.';
            return null;
        }

        $user = reset($users);

        if (
            $user->password_hash === null ||
            !password_verify($this->password, $user->password_hash)
        ) {
            $this->errors[] = 'Invalid email or password.';
            return null;
        }

        $_SESSION['user_id'] = $user->user_id;

        $user->last_login_at = date('Y-m-d H:i:s');
        $mapper->save($user);

        header('Location: /');
        exit;
    }
}
