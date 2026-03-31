<?php

namespace DealNews\Chronicle\Controller;

use PageMill\MVC\ControllerAbstract;
use PageMill\MVC\ResponderAbstract;

/**
 * Sends a 404 response for unmatched routes.
 *
 * @package DealNews\Chronicle
 */
class NotFound extends ControllerAbstract {

    /**
     * Sends a 404 and exits without running the full MVC lifecycle.
     *
     * @return void
     */
    public function handleRequest(): void {
        http_response_code(404);
        echo 'Not Found';
        exit;
    }

    /**
     * Never reached — handleRequest() exits before the parent lifecycle
     * calls this method. Required by the abstract contract.
     *
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        throw new \LogicException('NotFound::getResponder() should never be called.');
    }

    /**
     * Never reached — handleRequest() exits before the parent lifecycle
     * calls this method. Required by the abstract contract.
     *
     * @return array<int, class-string>
     */
    protected function getModels(): array {
        return [];
    }
}
