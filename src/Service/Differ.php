<?php

namespace DealNews\Chronicle\Service;

/**
 * Computes a structured diff between two JSON strings.
 *
 * Returns an array of changes, each describing a field path and what
 * changed. Suitable for rendering a human-readable diff in a view.
 *
 * @package DealNews\Chronicle
 */
class Differ {

    /**
     * Compares two JSON strings and returns a list of changes.
     *
     * Each change entry contains:
     *   - path   string  Dot-notation path to the changed field
     *   - type   string  'added', 'removed', or 'changed'
     *   - before mixed   Previous value (null for added)
     *   - after  mixed   New value (null for removed)
     *
     * @param  string|null $before JSON string of the previous version
     * @param  string|null $after  JSON string of the current version
     * @return array<int, array<string, mixed>>
     */
    public function diff(?string $before, ?string $after): array {
        $before_data = $before !== null ? json_decode($before, true) : [];
        $after_data  = $after !== null ? json_decode($after, true) : [];

        if (!is_array($before_data)) {
            $before_data = [];
        }

        if (!is_array($after_data)) {
            $after_data = [];
        }

        return $this->compareArrays($before_data, $after_data, '');
    }

    /**
     * Recursively compares two arrays and accumulates change entries.
     *
     * @param  array<string, mixed> $before
     * @param  array<string, mixed> $after
     * @param  string               $prefix Dot-notation path prefix
     * @return array<int, array<string, mixed>>
     */
    protected function compareArrays(
        array $before,
        array $after,
        string $prefix
    ): array {
        $changes = [];
        $all_keys = array_unique(
            array_merge(array_keys($before), array_keys($after))
        );

        foreach ($all_keys as $key) {
            $path = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;

            $in_before = array_key_exists($key, $before);
            $in_after  = array_key_exists($key, $after);

            if ($in_before && !$in_after) {
                $changes[] = [
                    'path'   => $path,
                    'type'   => 'removed',
                    'before' => $before[$key],
                    'after'  => null,
                ];
            } elseif (!$in_before && $in_after) {
                $changes[] = [
                    'path'   => $path,
                    'type'   => 'added',
                    'before' => null,
                    'after'  => $after[$key],
                ];
            } elseif (is_array($before[$key]) && is_array($after[$key])) {
                $changes = array_merge(
                    $changes,
                    $this->compareArrays($before[$key], $after[$key], $path)
                );
            } elseif ($before[$key] !== $after[$key]) {
                $changes[] = [
                    'path'   => $path,
                    'type'   => 'changed',
                    'before' => $before[$key],
                    'after'  => $after[$key],
                ];
            }
        }

        return $changes;
    }
}
