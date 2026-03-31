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

<form method="POST" action="<?= $form_action ?>">
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
    <button type="submit"><?= $editing ? 'Save Changes' : 'Add Type' ?></button>
<?php if ($editing): ?>
    <a href="/admin/types" class="btn btn-text">Cancel</a>
<?php endif; ?>
</form>

<?php if (empty($this->types)): ?>
    <p>No types configured yet.</p>
<?php else: ?>
    <?php $source_map = $this->buildSourceMap(); ?>
    <table>
        <thead>
            <tr>
                <th>Source</th>
                <th>Name</th>
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
                <td><?= $type->plugin !== null ? htmlspecialchars($type->plugin) : '—' ?></td>
                <td><?= htmlspecialchars($type->created_at) ?></td>
                <td class="row-actions">
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
    </table>
<?php endif; ?>
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
