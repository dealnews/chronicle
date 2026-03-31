<?php

namespace DealNews\Chronicle\Controller;

use DealNews\Chronicle\Action\IngestWebhook;
use DealNews\Chronicle\Responder\Webhook as WebhookResponder;
use PageMill\MVC\ControllerAbstract;
use PageMill\MVC\ResponderAbstract;

/**
 * Handles incoming webhook POST requests.
 *
 * Validates the API key from the Authorization header, reads the raw JSON
 * body, and delegates ingestion to the IngestWebhook action. The source
 * and type are provided as route tokens via $inputs.
 *
 * Route: POST /webhook/{source}/{type}
 *
 * @package DealNews\Chronicle
 */
class Webhook extends ControllerAbstract {

    /**
     * Adds the Authorization header value and raw request body to inputs
     * so they are available to actions.
     *
     * @param  array<int, array<string, mixed>> $filters
     * @return void
     */
    protected function filterInput(array $filters): void {
        parent::filterInput($filters);
        $this->inputs['authorization'] = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $this->inputs['body']          = (string) file_get_contents('php://input');
    }

    /**
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new WebhookResponder();
    }

    /**
     * API key validation and log ingestion happen entirely in the request
     * action; no models are needed for the webhook response.
     *
     * @return array<int, class-string>
     */
    protected function getRequestActions(): array {
        return [
            IngestWebhook::class,
        ];
    }

    /**
     * @return array<int, class-string>
     */
    protected function getModels(): array {
        return [];
    }
}
