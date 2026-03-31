<?php

namespace DealNews\Chronicle\View\Webhook;

use PageMill\MVC\View\JSONAbstract;

/**
 * JSON response view for webhook ingestion.
 *
 * Sets the HTTP status code from the action result and returns a
 * structured success or error payload.
 *
 * @package DealNews\Chronicle
 */
class JSON extends JSONAbstract {

    /**
     * HTTP status code set by the IngestWebhook action.
     *
     * @var int
     */
    protected int $http_status = 201;

    /**
     * Log ID on successful ingestion.
     *
     * @var int
     */
    protected int $log_id = 0;

    /**
     * Errors collected during the request lifecycle.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Sets the HTTP status code before generating output.
     *
     * @return void
     */
    public function generate(): void {
        $this->http_response->status($this->http_status);
        parent::generate();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array {
        if (!empty($this->errors)) {
            return [
                'success' => false,
                'errors'  => $this->errors,
            ];
        }

        return [
            'success' => true,
            'log_id'  => $this->log_id,
        ];
    }
}
