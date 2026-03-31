<?php

namespace DealNews\Chronicle\Responder;

use DealNews\Chronicle\View\Webhook\JSON;
use PageMill\HTTP\HTTP;
use PageMill\MVC\ResponderAbstract;

/**
 * Responder for webhook ingestion requests.
 *
 * Returns JSON only. The view class is chosen based on whether
 * any errors were produced by the ingestion action.
 *
 * @package DealNews\Chronicle
 */
class Webhook extends ResponderAbstract {

    /**
     * @return array<int, string>
     */
    protected function getAcceptedContentTypes(): array {
        return [HTTP::CONTENT_TYPE_JSON];
    }

    /**
     * @param  array<string, mixed> $data
     * @param  array<string, mixed> $inputs
     * @return string
     */
    protected function getView(array $data, array $inputs): string {
        return JSON::class;
    }
}
