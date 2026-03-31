<?php

namespace DealNews\Chronicle\Responder;

use DealNews\Chronicle\View\History\LogDetail;
use DealNews\Chronicle\View\History\LogList;
use DealNews\Chronicle\View\History\SourceList;
use DealNews\Chronicle\View\History\TypeList;
use PageMill\HTTP\HTTP;
use PageMill\MVC\ResponderAbstract;

/**
 * Responder for history display routes.
 *
 * Selects the correct HTML view based on which route tokens are present
 * in inputs, mirroring the model selection logic in the History controller.
 *
 * @package DealNews\Chronicle
 */
class History extends ResponderAbstract {

    /**
     * @return array<int, string>
     */
    protected function getAcceptedContentTypes(): array {
        return [HTTP::CONTENT_TYPE_HTML];
    }

    /**
     * Strips route token keys that are superseded by model data objects,
     * preventing PropertyMap from trying to overwrite an already-mapped
     * Data object with a plain string.
     *
     * @param  string $view
     * @param  array<string, mixed> $data
     * @param  array<string, mixed> $inputs
     * @return void
     */
    protected function generateView(string $view, array $data, array $inputs): void {
        foreach (['source', 'type', 'object_id'] as $key) {
            if (array_key_exists($key, $data)) {
                unset($inputs[$key]);
            }
        }

        parent::generateView($view, $data, $inputs);
    }

    /**
     * @param  array<string, mixed> $data
     * @param  array<string, mixed> $inputs
     * @return string
     */
    protected function getView(array $data, array $inputs): string {
        if (!empty($inputs['object_id'])) {
            return LogDetail::class;
        }

        if (!empty($inputs['type'])) {
            return LogList::class;
        }

        if (!empty($inputs['source'])) {
            return TypeList::class;
        }

        return SourceList::class;
    }
}
