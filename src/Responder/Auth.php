<?php

namespace DealNews\Chronicle\Responder;

use DealNews\Chronicle\View\Auth\Login;
use PageMill\HTTP\HTTP;
use PageMill\MVC\ResponderAbstract;

/**
 * Responder for authentication routes.
 *
 * Typically actions on these routes perform redirects directly, but
 * the login page itself is rendered as HTML when no redirect occurs.
 *
 * @package DealNews\Chronicle
 */
class Auth extends ResponderAbstract {

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
        return Login::class;
    }
}
