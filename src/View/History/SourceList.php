<?php

namespace DealNews\Chronicle\View\History;

use DealNews\Chronicle\Data\Source;
use DealNews\Chronicle\View\AbstractHTML;

/**
 * Lists all configured sources.
 *
 * @package DealNews\Chronicle
 */
class SourceList extends AbstractHTML {

    /**
     * @var array<int, Source>
     */
    protected array $sources = [];

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $this->page_title = 'Sources — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        ?>
<h1>Sources</h1>
<?php if (empty($this->sources)): ?>
    <p>No sources configured. <a href="/admin/sources">Add one</a>.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($this->sources as $source): ?>
            <tr>
                <td><a href="/<?= htmlspecialchars($source->name) ?>"><?= htmlspecialchars($source->name) ?></a></td>
                <td><?= $source->description !== null ? htmlspecialchars($source->description) : '' ?></td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php
    }
}
