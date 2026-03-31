<?php

namespace DealNews\Chronicle\View\History;

use DealNews\Chronicle\Data\Log;
use DealNews\Chronicle\Data\Source;
use DealNews\Chronicle\Data\Type;
use DealNews\Chronicle\Service\Differ;
use DealNews\Chronicle\View\AbstractHTML;

/**
 * Shows the full version history for an object with diffs between
 * consecutive versions.
 *
 * @package DealNews\Chronicle
 */
class LogDetail extends AbstractHTML {

    /**
     * @var Source|null
     */
    protected $source = null;

    /**
     * @var Type|null
     */
    protected $type = null;

    /**
     * @var string
     */
    protected string $object_id = '';

    /**
     * @var array<int, Log>
     */
    protected array $logs = [];

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $this->page_title = htmlspecialchars($this->object_id) . ' — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        if ($this->source === null || $this->type === null) {
            echo '<p>Source or type not found.</p>';
            return;
        }

        $source_name = htmlspecialchars($this->source->name);
        $type_name   = htmlspecialchars($this->type->name);
        $object_id   = htmlspecialchars($this->object_id);
        ?>
<div class="breadcrumb">
    <a href="/">Sources</a> <span>&rsaquo;</span>
    <a href="/<?= $source_name ?>"><?= $source_name ?></a> <span>&rsaquo;</span>
    <a href="/<?= $source_name ?>/<?= $type_name ?>"><?= $type_name ?></a> <span>&rsaquo;</span>
    <?= $object_id ?>
</div>
<h1><?= $object_id ?></h1>

<?php if (empty($this->logs)): ?>
    <p>No log entries found for this object.</p>
<?php else: ?>
    <?php $differ = new Differ(); ?>
    <?php foreach ($this->logs as $index => $log): ?>
        <?php $prev = $index > 0 ? $this->logs[$index - 1] : null; ?>
        <section class="log-entry log-entry--<?= htmlspecialchars($log->action) ?>">
            <header class="log-entry__header">
                <span class="log-entry__action"><?= htmlspecialchars($log->action) ?></span>
                <span class="log-entry__date"><?= htmlspecialchars($log->change_date) ?></span>
                <?php if ($log->updated_by !== null): ?>
                    <span class="log-entry__author">by <?= htmlspecialchars($log->updated_by) ?></span>
                <?php endif; ?>
                <?php if ($log->version !== null): ?>
                    <span class="log-entry__version">Version: <?= htmlspecialchars($log->version) ?></span>
                <?php endif; ?>
            </header>

            <?php if ($prev !== null): ?>
                <?php $changes = $differ->diff($prev->data, $log->data); ?>
                <?php if (!empty($changes)): ?>
                    <table class="diff">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Change</th>
                                <th>Before</th>
                                <th>After</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($changes as $change): ?>
                            <tr class="diff__row diff__row--<?= $change['type'] ?>">
                                <td><?= htmlspecialchars($change['path']) ?></td>
                                <td><?= htmlspecialchars($change['type']) ?></td>
                                <td><?= htmlspecialchars($this->formatValue($change['before'])) ?></td>
                                <td><?= htmlspecialchars($this->formatValue($change['after'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="diff__no-changes">No field-level changes detected.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="diff__initial">Initial version — full data:</p>
                <pre><?= htmlspecialchars((string) $log->data) ?></pre>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php
    }

    /**
     * Formats a diff value for display, handling null and non-scalar types.
     *
     * @param  mixed $value
     * @return string
     */
    protected function formatValue(mixed $value): string {
        if ($value === null) {
            return '—';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
