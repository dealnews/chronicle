<?php

namespace DealNews\Chronicle\Action;

use DealNews\Chronicle\Data\User;
use DealNews\Chronicle\Mapper\User as UserMapper;
use DealNews\DB\CRUD;

/**
 * Creates the initial admin user during first-run setup.
 *
 * Re-checks that no users exist before creating the account, so this
 * endpoint cannot be used to add users once the system is initialized.
 *
 * @package DealNews\Chronicle
 */
class CreateFirstUser extends AbstractCsrfAction {

    /**
     * Display name for the new user.
     *
     * @var string
     */
    protected string $name = '';

    /**
     * Email address for the new user.
     *
     * @var string
     */
    protected string $email = '';

    /**
     * Plaintext password — hashed before storage, never persisted.
     *
     * @var string
     */
    protected string $password = '';

    /**
     * @param  array<string, mixed> $data
     * @return null
     */
    protected function doCsrfAction(array $data = []): mixed {
        if (empty($this->name) || empty($this->email) || empty($this->password)) {
            $this->errors[] = 'Name, email, and password are all required.';
            return null;
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Please enter a valid email address.';
            return null;
        }

        if (strlen($this->password) < 8) {
            $this->errors[] = 'Password must be at least 8 characters.';
            return null;
        }

        // Re-check inside the action so this cannot be called once users exist.
        $crud  = CRUD::factory('chronicle');
        $sth   = $crud->run('SELECT COUNT(*) FROM chronicle_users');
        $count = (int) $sth->fetchColumn();

        if ($count > 0) {
            $this->errors[] = 'Setup is already complete. Please sign in.';
            return null;
        }

        $user                = new User();
        $user->name          = $this->name;
        $user->email         = $this->email;
        $user->password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        $mapper = new UserMapper();
        $user   = $mapper->save($user);

        $_SESSION['user_id'] = $user->user_id;

        header('Location: /');
        exit;
    }
}
