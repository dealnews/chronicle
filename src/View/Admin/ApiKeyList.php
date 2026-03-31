<?php

namespace DealNews\Chronicle\View\Admin;

use DealNews\Chronicle\Data\ApiKey;
use DealNews\Chronicle\View\AbstractHTML;

/**
 * Admin page for listing, creating, and revoking API keys.
 *
 * When a key has just been created, $new_api_key contains the plaintext
 * key for one-time display. It is never shown again after this page load.
 *
 * @package DealNews\Chronicle
 */
class ApiKeyList extends AbstractHTML {

    /**
     * @var array<int, ApiKey>
     */
    protected array $api_keys = [];

    /**
     * Plaintext key returned by SaveApiKey on creation, empty otherwise.
     *
     * @var string
     */
    protected string $new_api_key = '';

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $this->page_title = 'API Keys — Admin — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        ?>
<h1>API Keys</h1>

<?php if (!empty($this->new_api_key)): ?>
    <div class="alert alert--success">
        <strong>New API key created.</strong> Copy it now — it will not be shown again.<br>
        <code><?= htmlspecialchars($this->new_api_key) ?></code>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/api-keys">
    <?= $this->csrfField() ?>
    <div class="field">
        <label for="name">Label</label>
        <input type="text" id="name" name="name" required>
    </div>
    <button type="submit">Create Key</button>
</form>

<?php if (empty($this->api_keys)): ?>
    <p>No API keys created yet.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Label</th>
                <th>Created</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($this->api_keys as $key): ?>
            <tr>
                <td><?= htmlspecialchars($key->name) ?></td>
                <td><?= htmlspecialchars($key->created_at) ?></td>
                <td><?= $key->revoked_at !== null ? 'Revoked ' . htmlspecialchars($key->revoked_at) : 'Active' ?></td>
                <td>
<?php if ($key->revoked_at === null): ?>
                    <form method="POST" action="/admin/api-keys/<?= $key->api_key_id ?>">
                        <?= $this->csrfField() ?>
                        <button type="submit" onclick="return confirm('Revoke this key?')">Revoke</button>
                    </form>
<?php endif; ?>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php
    }
}
