<?php

namespace DealNews\Chronicle\Controller;

use DealNews\Chronicle\Model\LogDetail;
use DealNews\Chronicle\Model\LogList;
use DealNews\Chronicle\Model\SourceList;
use DealNews\Chronicle\Model\TypeList;
use DealNews\Chronicle\Responder\History as HistoryResponder;
use PageMill\MVC\ResponderAbstract;

/**
 * Displays object history, scoped by the route tokens present in inputs.
 *
 * Routes:
 *   GET /                              - List all sources.
 *   GET /{source}                      - List types for a source.
 *   GET /{source}/{type}               - List log entries for a type.
 *   GET /{source}/{type}/{object_id}   - Show version history and diffs.
 *
 * @package DealNews\Chronicle
 */
class History extends AbstractAuthenticated {

    /**
     * Captures pagination and filter query parameters.
     *
     * @param  array<int, array<string, mixed>> $filters
     * @return void
     */
    protected function filterInput(array $filters): void {
        parent::filterInput([
            INPUT_GET => [
                'page'  => [
                    'filter'  => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'default' => 1],
                ],
            ],
        ]);
    }

    /**
     * @return ResponderAbstract
     */
    protected function getResponder(): ResponderAbstract {
        return new HistoryResponder();
    }

    /**
     * Selects the appropriate model based on which route tokens are present.
     *
     * @return array<int, class-string>
     */
    protected function getModels(): array {
        if (!empty($this->inputs['object_id'])) {
            return [LogDetail::class];
        }

        if (!empty($this->inputs['type'])) {
            return [LogList::class];
        }

        if (!empty($this->inputs['source'])) {
            return [TypeList::class];
        }

        return [SourceList::class];
    }
}
