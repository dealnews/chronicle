<?php

namespace DealNews\Chronicle\Controller;

use DealNews\Chronicle\Action\Admin\SaveApiKey;
use DealNews\Chronicle\Action\Admin\SaveSource;
use DealNews\Chronicle\Action\Admin\SaveType;
use DealNews\Chronicle\Action\Admin\SaveUser;
use DealNews\Chronicle\Model\Admin\ApiKeyList;
use DealNews\Chronicle\Model\Admin\SourceList;
use DealNews\Chronicle\Model\Admin\TypeList;
use DealNews\Chronicle\Model\Admin\UserList;
use DealNews\Chronicle\Responder\Admin as AdminResponder;
use PageMill\MVC\ResponderAbstract;

/**
 * Handles admin CRUD routes for sources, types, API keys, and users.
 *
 * The sub-page is derived from the request path:
 *   GET/POST /admin/sources       - List and create sources.
 *   GET/POST /admin/types         - List and create types.
 *   GET/POST /admin/api-keys      - List and create API keys.
 *   DELETE   /admin/api-keys/{id} - Revoke an API key.
 *   GET/POST /admin/users         - List and create users.
 *   GET/POST /admin/users/{id}    - Edit or delete a user.
 *
 * @package DealNews\Chronicle
 */
class Admin extends AbstractAuthenticated {

    /**
     * Parses the admin sub-page from the request path, captures any
     * trailing numeric ID segment, and filters POST fields.
     *
     * @param  array<int, array<string, mixed>> $filters
     * @return void
     */
    protected function filterInput(array $filters): void {
        parent::filterInput([
            INPUT_POST => [
                'name'        => FILTER_DEFAULT,
                'description' => FILTER_DEFAULT,
                'email'       => FILTER_SANITIZE_EMAIL,
                'password'    => FILTER_DEFAULT,
                'plugin'      => FILTER_DEFAULT,
                'source_id'   => FILTER_VALIDATE_INT,
                '_delete'     => FILTER_DEFAULT,
                '_csrf_token' => FILTER_DEFAULT,
            ],
        ]);

        // Strip leading /admin and split remaining path segments.
        $sub_path = ltrim(substr($this->request_path, strlen('/admin')), '/');
        $segments = $sub_path !== '' ? explode('/', $sub_path, 2) : [];

        $this->inputs['admin_section'] = $segments[0] ?? '';
        $this->inputs['admin_id']      = isset($segments[1]) && ctype_digit($segments[1])
            ? (int) $segments[1]
            : 0;
    }

    /**
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new AdminResponder();
    }

    /**
     * Routes POST/DELETE mutations to the appropriate action.
     *
     * @return array<int, class-string>
     */
    protected function getRequestActions(): array {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST' || $method === 'DELETE') {
            return match ($this->inputs['admin_section']) {
                'sources'  => [SaveSource::class],
                'types'    => [SaveType::class],
                'api-keys' => [SaveApiKey::class],
                'users'    => [SaveUser::class],
                default    => [],
            };
        }

        return [];
    }

    /**
     * Selects the model that matches the current admin section.
     *
     * @return array<int, class-string>
     */
    protected function getModels(): array {
        return match ($this->inputs['admin_section']) {
            'sources'  => [SourceList::class],
            'types'    => [TypeList::class],
            'api-keys' => [ApiKeyList::class],
            'users'    => [UserList::class],
            default    => [],
        };
    }
}
