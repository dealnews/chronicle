<?php

namespace DealNews\Chronicle\View\Admin;

use DealNews\Chronicle\Data\User;
use DealNews\Chronicle\View\AbstractHTML;

/**
 * Admin page for listing, creating, editing, and deleting users.
 *
 * When edit_user is set, the form renders in edit mode pre-populated
 * with that user's values and posts to /admin/users/{id}.
 *
 * @package DealNews\Chronicle
 */
class UserList extends AbstractHTML {

    /**
     * @var array<int, User>
     */
    protected array $users = [];

    /**
     * User being edited, or null when creating.
     *
     * @var User|null
     */
    protected $edit_user = null;

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $this->page_title = 'Users — Admin — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        $editing     = $this->edit_user !== null;
        $form_action = $editing
            ? '/admin/users/' . $this->edit_user->user_id
            : '/admin/users';
        ?>
<h1>Users</h1>

<form method="POST" action="<?= $form_action ?>">
    <?= $this->csrfField() ?>
    <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?= $editing ? htmlspecialchars($this->edit_user->email) : '' ?>">
    </div>
    <div class="field">
        <label for="name">Name</label>
        <input type="text" id="name" name="name"
               value="<?= $editing ? htmlspecialchars((string) $this->edit_user->name) : '' ?>">
    </div>
    <div class="field">
        <label for="password"><?= $editing ? 'New Password' : 'Password' ?></label>
        <input type="password" id="password" name="password"
               autocomplete="new-password"
               <?= $editing ? '' : 'required' ?>>
<?php if ($editing): ?>
        <p class="field-hint">Leave blank to keep the existing password.</p>
<?php endif; ?>
    </div>
    <button type="submit"><?= $editing ? 'Save Changes' : 'Add User' ?></button>
<?php if ($editing): ?>
    <a href="/admin/users" class="btn btn-text">Cancel</a>
<?php endif; ?>
</form>

<?php if (empty($this->users)): ?>
    <p>No users configured yet.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Last Login</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($this->users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user->email) ?></td>
                <td><?= htmlspecialchars((string) $user->name) ?></td>
                <td><?= $user->last_login_at !== null ? htmlspecialchars($user->last_login_at) : '&mdash;' ?></td>
                <td><?= htmlspecialchars($user->created_at) ?></td>
                <td class="row-actions">
                    <a href="/admin/users/<?= $user->user_id ?>" class="btn btn-text">Edit</a>
                    <form method="POST" action="/admin/users/<?= $user->user_id ?>">
                        <?= $this->csrfField() ?>
                        <input type="hidden" name="_delete" value="1">
                        <button type="submit" class="btn-text"
                                onclick="return confirm('Delete user &quot;<?= htmlspecialchars($user->email, ENT_QUOTES) ?>&quot;? This cannot be undone.')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php
    }
}
