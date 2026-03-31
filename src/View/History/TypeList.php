<?php

namespace DealNews\Chronicle\View\History;

use DealNews\Chronicle\Data\Source;
use DealNews\Chronicle\Data\Type;
use DealNews\Chronicle\View\AbstractHTML;

/**
 * Lists all types for a source.
 *
 * @package DealNews\Chronicle
 */
class TypeList extends AbstractHTML {

    /**
     * @var Source|null
     */
    protected $source = null;

    /**
     * @var array<int, Type>
     */
    protected array $types = [];

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $name             = $this->source ? $this->source->name : '';
        $this->page_title = htmlspecialchars($name) . ' — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        if ($this->source === null) {
            echo '<p>Source not found.</p>';
            return;
        }

        $source_name = htmlspecialchars($this->source->name);
        ?>
<div class="breadcrumb">
    <a href="/">Sources</a> <span>&rsaquo;</span> <?= $source_name ?>
</div>
<h1><?= $source_name ?></h1>
<?php if (empty($this->types)): ?>
    <p>No types configured for this source. <a href="/admin/types">Add one</a>.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($this->types as $type): ?>
            <tr>
                <td>
                    <a href="/<?= $source_name ?>/<?= htmlspecialchars($type->name) ?>">
                        <?= htmlspecialchars($type->name) ?>
                    </a>
                </td>
                <td><?= $type->description !== null ? htmlspecialchars($type->description) : '' ?></td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php
    }
}
