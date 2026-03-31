<?php

namespace DealNews\Chronicle\Action;

use PageMill\MVC\ActionAbstract;

/**
 * Base action that validates a CSRF token before executing.
 *
 * Subclasses implement doCsrfAction() instead of doAction(). The token
 * is compared against $_SESSION['csrf_token'] using a timing-safe check.
 *
 * @package DealNews\Chronicle
 */
abstract class AbstractCsrfAction extends ActionAbstract {

    /**
     * CSRF token submitted with the form.
     *
     * @var string
     */
    protected string $_csrf_token = '';

    /**
     * Validates the CSRF token, then delegates to doCsrfAction().
     *
     * @param  array<string, mixed> $data
     * @return mixed
     */
    public function doAction(array $data = []): mixed {
        $expected = $_SESSION['csrf_token'] ?? '';

        if (empty($this->_csrf_token) || !hash_equals($expected, $this->_csrf_token)) {
            $this->errors[] = 'Invalid or expired form submission. Please try again.';
            return null;
        }

        return $this->doCsrfAction($data);
    }

    /**
     * Subclasses implement their logic here instead of doAction().
     *
     * @param  array<string, mixed> $data
     * @return mixed
     */
    abstract protected function doCsrfAction(array $data = []): mixed;
}
