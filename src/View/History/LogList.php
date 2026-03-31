<?php

namespace DealNews\Chronicle\View\History;

use DealNews\Chronicle\Data\Source;
use DealNews\Chronicle\Data\Type;
use DealNews\Chronicle\View\AbstractHTML;

/**
 * Paginated list of log entries for a source and type.
 *
 * @package DealNews\Chronicle
 */
class LogList extends AbstractHTML {

    /**
     * @var Source|null
     */
    protected $source = null;

    /**
     * @var Type|null
     */
    protected $type = null;

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $source_name      = $this->source ? $this->source->name : '';
        $type_name        = $this->type ? $this->type->name : '';
        $this->page_title = "{$source_name} / {$type_name} — Chronicle";
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
        $base_path   = "/{$source_name}/{$type_name}";
        ?>
<div class="breadcrumb">
    <a href="/">Sources</a> <span>&rsaquo;</span>
    <a href="/<?= $source_name ?>"><?= $source_name ?></a> <span>&rsaquo;</span>
    <?= $type_name ?>
</div>
<h1><?= $type_name ?></h1>
<form class="object-lookup"
      onsubmit="event.preventDefault();
                var id = this.querySelector('input').value.trim();
                if (id) window.location = '<?= $base_path ?>/' + encodeURIComponent(id);">
    <div class="field">
        <label for="object_id">Object ID</label>
        <input type="text" id="object_id" name="object_id" required autofocus
               placeholder="Enter an object ID&hellip;">
    </div>
    <button type="submit">Look Up</button>
</form>
<?php
    }
}
