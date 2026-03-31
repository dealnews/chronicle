<?php

namespace DealNews\Chronicle\View\Admin;

use DealNews\Chronicle\Data\Source;
use DealNews\Chronicle\View\AbstractHTML;

/**
 * Admin page for listing, creating, editing, and deleting sources.
 *
 * When edit_source is set, the form renders in edit mode pre-populated
 * with that source's values and posts to /admin/sources/{id}.
 *
 * @package DealNews\Chronicle
 */
class SourceList extends AbstractHTML {

    /**
     * @var array<int, Source>
     */
    protected array $sources = [];

    /**
     * Source being edited, or null when creating.
     *
     * @var Source|null
     */
    protected $edit_source = null;

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $this->page_title = 'Sources — Admin — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        $editing     = $this->edit_source !== null;
        $form_action = $editing
            ? '/admin/sources/' . $this->edit_source->source_id
            : '/admin/sources';
        ?>
<h1>Sources</h1>

<form method="POST" action="<?= $form_action ?>" class="form--stacked">
    <?= $this->csrfField() ?>
    <div class="field">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required
               value="<?= $editing ? htmlspecialchars($this->edit_source->name) : '' ?>">
    </div>
    <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"><?= $editing ? htmlspecialchars((string) $this->edit_source->description) : '' ?></textarea>
    </div>
    <div class="form-actions">
        <button type="submit"><?= $editing ? 'Save Changes' : 'Add Source' ?></button>
<?php if ($editing): ?>
        <a href="/admin/sources" class="btn btn-text">Cancel</a>
<?php endif; ?>
    </div>
</form>

<?php if (empty($this->sources)): ?>
    <p>No sources configured yet.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($this->sources as $source): ?>
            <tr>
                <td><?= htmlspecialchars($source->name) ?></td>
                <td><?= $source->description !== null ? htmlspecialchars($source->description) : '&mdash;' ?></td>
                <td><?= htmlspecialchars($source->created_at) ?></td>
                <td class="row-actions">
                    <a href="/admin/sources/<?= $source->source_id ?>" class="btn btn-text">Edit</a>
                    <form method="POST" action="/admin/sources/<?= $source->source_id ?>">
                        <?= $this->csrfField() ?>
                        <input type="hidden" name="_delete" value="1">
                        <button type="submit" class="btn-text"
                                onclick="return confirm('Delete source &quot;<?= htmlspecialchars($source->name, ENT_QUOTES) ?>&quot;? This cannot be undone.')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php
    }
}
