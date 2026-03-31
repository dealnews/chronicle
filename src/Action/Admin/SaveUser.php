<?php

namespace DealNews\Chronicle\Action\Admin;

use DealNews\Chronicle\Action\AbstractCsrfAction;
use DealNews\Chronicle\Data\User;
use DealNews\Chronicle\Mapper\User as UserMapper;

/**
 * Creates, updates, or deletes a user.
 *
 *   POST /admin/users        admin_id == 0 → create
 *   POST /admin/users/{id}   admin_id  > 0 → update
 *   POST /admin/users/{id}   admin_id  > 0, _delete set → delete
 *
 * Password is only stored when provided. On update, an empty password
 * field leaves the existing password_hash unchanged.
 *
 * @package DealNews\Chronicle
 */
class SaveUser extends AbstractCsrfAction {

    /**
     * Email address from POST body.
     *
     * @var string
     */
    protected string $email = '';

    /**
     * Display name from POST body.
     *
     * @var string
     */
    protected string $name = '';

    /**
     * Plaintext password from POST body. Empty means no change on update.
     *
     * @var string
     */
    protected string $password = '';

    /**
     * User ID parsed from the URL path (> 0 triggers update or delete).
     *
     * @var int
     */
    protected int $admin_id = 0;

    /**
     * When set, deletes the user instead of saving it.
     *
     * @var string|null
     */
    protected ?string $_delete = null;

    /**
     * @param  array<string, mixed> $data
     * @return null
     */
    protected function doCsrfAction(array $data = []): mixed {
        $mapper = new UserMapper();

        if ($this->admin_id > 0 && $this->_delete !== null) {
            return $this->deleteUser($mapper);
        }

        if ($this->admin_id > 0) {
            return $this->updateUser($mapper);
        }

        return $this->createUser($mapper);
    }

    /**
     * @param  UserMapper $mapper
     * @return null
     */
    protected function createUser(UserMapper $mapper): mixed {
        if (empty($this->email)) {
            $this->errors[] = 'Email address is required.';
            return null;
        }

        $existing = $mapper->find(['email' => $this->email]);
        if (!empty($existing)) {
            $this->errors[] = 'A user with that email address already exists.';
            return null;
        }

        $user        = new User();
        $user->email = $this->email;
        $user->name  = $this->name !== '' ? $this->name : null;

        if ($this->password !== '') {
            $user->password_hash = password_hash($this->password, PASSWORD_DEFAULT);
        }

        $mapper->save($user);

        header('Location: /admin/users');
        exit;
    }

    /**
     * @param  UserMapper $mapper
     * @return null
     */
    protected function updateUser(UserMapper $mapper): mixed {
        if (empty($this->email)) {
            $this->errors[] = 'Email address is required.';
            return null;
        }

        $user = $mapper->load($this->admin_id);

        if ($user === null) {
            $this->errors[] = 'User not found.';
            return null;
        }

        $user->email = $this->email;
        $user->name  = $this->name !== '' ? $this->name : null;

        if ($this->password !== '') {
            $user->password_hash = password_hash($this->password, PASSWORD_DEFAULT);
        }

        $mapper->save($user);

        header('Location: /admin/users');
        exit;
    }

    /**
     * @param  UserMapper $mapper
     * @return null
     */
    protected function deleteUser(UserMapper $mapper): mixed {
        $user = $mapper->load($this->admin_id);

        if ($user === null) {
            $this->errors[] = 'User not found.';
            return null;
        }

        $mapper->delete($user);

        header('Location: /admin/users');
        exit;
    }
}
