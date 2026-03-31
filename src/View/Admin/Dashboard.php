<?php

namespace DealNews\Chronicle\View\Admin;

use DealNews\Chronicle\View\AbstractHTML;

/**
 * Admin landing page.
 *
 * @package DealNews\Chronicle
 */
class Dashboard extends AbstractHTML {

    /**
     * @return void
     */
    protected function prepareDocument(): void {
        $this->page_title = 'Admin — Chronicle';
    }

    /**
     * @return void
     */
    protected function generateBody(): void {
        ?>
<h1>Admin</h1>
<ul>
    <li><a href="/admin/sources">Sources</a></li>
    <li><a href="/admin/types">Types</a></li>
    <li><a href="/admin/api-keys">API Keys</a></li>
</ul>
<?php
    }
}
