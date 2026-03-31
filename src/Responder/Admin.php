<?php

namespace DealNews\Chronicle\Responder;

use DealNews\Chronicle\View\Admin\ApiKeyList;
use DealNews\Chronicle\View\Admin\Dashboard;
use DealNews\Chronicle\View\Admin\SourceList;
use DealNews\Chronicle\View\Admin\TypeList;
use DealNews\Chronicle\View\Admin\UserList;
use PageMill\HTTP\HTTP;
use PageMill\MVC\ResponderAbstract;

/**
 * Responder for admin routes.
 *
 * Selects the HTML view based on the admin_section input parsed
 * from the request path by the Admin controller.
 *
 * @package DealNews\Chronicle
 */
class Admin extends ResponderAbstract {

    /**
     * @return array<int, string>
     */
    protected function getAcceptedContentTypes(): array {
        return [HTTP::CONTENT_TYPE_HTML];
    }

    /**
     * @param  array<string, mixed> $data
     * @param  array<string, mixed> $inputs
     * @return string
     */
    protected function getView(array $data, array $inputs): string {
        return match ($inputs['admin_section'] ?? '') {
            'sources'  => SourceList::class,
            'types'    => TypeList::class,
            'api-keys' => ApiKeyList::class,
            'users'    => UserList::class,
            default    => Dashboard::class,
        };
    }
}
