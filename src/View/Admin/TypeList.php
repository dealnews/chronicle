<?php

namespace DealNews\Chronicle\View\Admin;

use DealNews\Chronicle\Data\Source;
use DealNews\Chronicle\Data\Type;
use DealNews\Chronicle\Plugins\AbstractPlugin;
use DealNews\Chronicle\View\AbstractHTML;

/**
 * Admin page for listing, creating, editing, and deleting types.
 *
 * When edit_type is set, the form renders in edit mode pre-populated with
 * that type's values and posts to /admin/types/{id}.
 *
 * @package DealNews\Chronicle
 */
class TypeList extends AbstractHTML {

    /**
     * @var array<int, Source>
     */
    protected array $sources = [];

    /**
     * @var array<int, Type>
     */
    protected array $types = [];

    /**
     * Type being edited, or null when creating.
     *
     * @var Type|null
     */
    protected $edit_type = null;

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $this->page_title = 'Types — Admin — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        $editing     = $this->edit_type !== null;
        $form_action = $editing
            ? '/admin/types/' . $this->edit_type->type_id
            : '/admin/types';
        ?>
<h1>Types</h1>

<form method="POST" action="<?= $form_action ?>" class="form--stacked">
    <?= $this->csrfField() ?>
    <div class="field">
        <label for="source_id">Source</label>
        <select id="source_id" name="source_id" required>
            <option value="">— select a source —</option>
<?php foreach ($this->sources as $source): ?>
            <option value="<?= $source->source_id ?>"
                <?= ($editing && $this->edit_type->source_id === $source->source_id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($source->name) ?>
            </option>
<?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required
               value="<?= $editing ? htmlspecialchars($this->edit_type->name) : '' ?>">
    </div>
    <div class="field">
        <label for="plugin">Plugin</label>
        <select id="plugin" name="plugin" required>
            <option value="">— select a plugin —</option>
<?php foreach (AbstractPlugin::getAvailable() as $short_name => $label): ?>
            <option value="<?= htmlspecialchars($short_name) ?>"
                <?= ($editing && $this->edit_type->plugin === $short_name) ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </option>
<?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"><?= $editing ? htmlspecialchars((string) $this->edit_type->description) : '' ?></textarea>
    </div>
    <div class="form-actions">
        <button type="submit"><?= $editing ? 'Save Changes' : 'Add Type' ?></button>
<?php if ($editing): ?>
        <a href="/admin/types" class="btn btn-text">Cancel</a>
<?php endif; ?>
    </div>
</form>

<?php if (empty($this->types)): ?>
    <p>No types configured yet.</p>
<?php else: ?>
    <?php $source_map = $this->buildSourceMap(); ?>
    <div class="table-wrap"><table>
        <thead>
            <tr>
                <th>Source</th>
                <th>Name</th>
                <th>Description</th>
                <th>Plugin</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($this->types as $type): ?>
            <tr>
                <td><?= htmlspecialchars($source_map[$type->source_id] ?? '—') ?></td>
                <td><?= htmlspecialchars($type->name) ?></td>
                <td><?= $type->description !== null ? htmlspecialchars($type->description) : '&mdash;' ?></td>
                <td><?= $type->plugin !== null ? htmlspecialchars($type->plugin) : '—' ?></td>
                <td><?= htmlspecialchars($type->created_at) ?></td>
                <td class="row-actions">
                    <button type="button" class="btn-text"
                            title="Copy Webhook"
                            data-webhook="/webhook/<?= rawurlencode($source_map[$type->source_id] ?? '') ?>/<?= rawurlencode($type->name) ?>"
                            onclick="copyWebhookUrl(this)">
                        Webhook <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 115.77 122.88" width="14" height="14" fill="currentColor" aria-hidden="true" style="vertical-align:-2px;margin-left:2px"><path fill-rule="evenodd" clip-rule="evenodd" d="M89.62,13.96v7.73h12.19h0.01v0.02c3.85,0.01,7.34,1.57,9.86,4.1c2.5,2.51,4.06,5.98,4.07,9.82h0.02v0.02 v73.27v0.01h-0.02c-0.01,3.84-1.57,7.33-4.1,9.86c-2.51,2.5-5.98,4.06-9.82,4.07v0.02h-0.02h-61.7H40.1v-0.02 c-3.84-0.01-7.34-1.57-9.86-4.1c-2.5-2.51-4.06-5.98-4.07-9.82h-0.02v-0.02V92.51H13.96h-0.01v-0.02c-3.84-0.01-7.34-1.57-9.86-4.1 c-2.5-2.51-4.06-5.98-4.07-9.82H0v-0.02V13.96v-0.01h0.02c0.01-3.85,1.58-7.34,4.1-9.86c2.51-2.5,5.98-4.06,9.82-4.07V0h0.02h61.7 h0.01v0.02c3.85,0.01,7.34,1.57,9.86,4.1c2.5,2.51,4.06,5.98,4.07,9.82h0.02V13.96L89.62,13.96z M79.04,21.69v-7.73v-0.02h0.02 c0-0.91-0.39-1.75-1.01-2.37c-0.61-0.61-1.46-1-2.37-1v0.02h-0.01h-61.7h-0.02v-0.02c-0.91,0-1.75,0.39-2.37,1.01 c-0.61,0.61-1,1.46-1,2.37h0.02v0.01v64.59v0.02h-0.02c0,0.91,0.39,1.75,1.01,2.37c0.61,0.61,1.46,1,2.37,1v-0.02h0.01h12.19V35.65 v-0.01h0.02c0.01-3.85,1.58-7.34,4.1-9.86c2.51-2.5,5.98-4.06,9.82-4.07v-0.02h0.02H79.04L79.04,21.69z M105.18,108.92V35.65v-0.02 h0.02c0-0.91-0.39-1.75-1.01-2.37c-0.61-0.61-1.46-1-2.37-1v0.02h-0.01h-61.7h-0.02v-0.02c-0.91,0-1.75,0.39-2.37,1.01 c-0.61,0.61-1,1.46-1,2.37h0.02v0.01v73.27v0.02h-0.02c0,0.91,0.39,1.75,1.01,2.37c0.61,0.61,1.46,1,2.37,1v-0.02h0.01h61.7h0.02 v0.02c0.91,0,1.75-0.39,2.37-1.01c0.61-0.61,1-1.46,1-2.37h-0.02V108.92L105.18,108.92z"/></svg>
                    </button>
                    <a href="/admin/types/<?= $type->type_id ?>" class="btn btn-text">Edit</a>
                    <form method="POST" action="/admin/types/<?= $type->type_id ?>">
                        <?= $this->csrfField() ?>
                        <input type="hidden" name="_delete" value="1">
                        <button type="submit" class="btn-text"
                                onclick="return confirm('Delete type &quot;<?= htmlspecialchars($type->name, ENT_QUOTES) ?>&quot;?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<script>
function copyWebhookUrl(btn) {
    var url = window.location.origin + btn.dataset.webhook;
    navigator.clipboard.writeText(url).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = 'Copied!';
        setTimeout(function() { btn.innerHTML = orig; }, 1500);
    });
}
</script>
<?php
    }

    /**
     * Builds a source_id => name lookup for the type table.
     *
     * @return array<int, string>
     */
    protected function buildSourceMap(): array {
        $map = [];
        foreach ($this->sources as $source) {
            $map[$source->source_id] = $source->name;
        }
        return $map;
    }
}
