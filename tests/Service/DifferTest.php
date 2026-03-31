<?php

namespace DealNews\Chronicle\Tests\Service;

use DealNews\Chronicle\Service\Differ;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Differ service.
 *
 * @package DealNews\Chronicle\Tests
 */
class DifferTest extends TestCase {

    protected Differ $differ;

    protected function setUp(): void {
        $this->differ = new Differ();
    }

    /**
     * Two identical JSON objects produce no changes.
     */
    public function testNoDifferences(): void {
        $json = json_encode(['foo' => 'bar', 'baz' => 1]);

        $changes = $this->differ->diff($json, $json);

        $this->assertSame([], $changes);
    }

    /**
     * A field present in after but not before is reported as added.
     */
    public function testAddedField(): void {
        $before = json_encode(['foo' => 'bar']);
        $after  = json_encode(['foo' => 'bar', 'new' => 'value']);

        $changes = $this->differ->diff($before, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('added',  $changes[0]['type']);
        $this->assertSame('new',    $changes[0]['path']);
        $this->assertNull($changes[0]['before']);
        $this->assertSame('value', $changes[0]['after']);
    }

    /**
     * A field present in before but not after is reported as removed.
     */
    public function testRemovedField(): void {
        $before = json_encode(['foo' => 'bar', 'old' => 'gone']);
        $after  = json_encode(['foo' => 'bar']);

        $changes = $this->differ->diff($before, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('removed', $changes[0]['type']);
        $this->assertSame('old',     $changes[0]['path']);
        $this->assertSame('gone',    $changes[0]['before']);
        $this->assertNull($changes[0]['after']);
    }

    /**
     * A field with a different value is reported as changed.
     */
    public function testChangedField(): void {
        $before = json_encode(['foo' => 'old']);
        $after  = json_encode(['foo' => 'new']);

        $changes = $this->differ->diff($before, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('changed', $changes[0]['type']);
        $this->assertSame('foo',     $changes[0]['path']);
        $this->assertSame('old',     $changes[0]['before']);
        $this->assertSame('new',     $changes[0]['after']);
    }

    /**
     * Multiple simultaneous changes are all reported.
     */
    public function testMultipleChanges(): void {
        $before = json_encode(['keep' => 1, 'change' => 'a', 'remove' => true]);
        $after  = json_encode(['keep' => 1, 'change' => 'b', 'add'    => 99]);

        $changes = $this->differ->diff($before, $after);

        $types = array_column($changes, 'type');
        $this->assertContains('changed', $types);
        $this->assertContains('removed', $types);
        $this->assertContains('added',   $types);
        $this->assertCount(3, $changes);
    }

    /**
     * Nested field changes are reported with dot-notation paths.
     */
    public function testNestedChangedField(): void {
        $before = json_encode(['parent' => ['child' => 'old']]);
        $after  = json_encode(['parent' => ['child' => 'new']]);

        $changes = $this->differ->diff($before, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('changed',       $changes[0]['type']);
        $this->assertSame('parent.child',  $changes[0]['path']);
        $this->assertSame('old',           $changes[0]['before']);
        $this->assertSame('new',           $changes[0]['after']);
    }

    /**
     * A nested field added inside an existing object is reported correctly.
     */
    public function testNestedAddedField(): void {
        $before = json_encode(['parent' => ['existing' => 1]]);
        $after  = json_encode(['parent' => ['existing' => 1, 'added' => 2]]);

        $changes = $this->differ->diff($before, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('added',          $changes[0]['type']);
        $this->assertSame('parent.added',   $changes[0]['path']);
    }

    /**
     * Deeply nested paths use dot notation throughout.
     */
    public function testDeeplyNestedPath(): void {
        $before = json_encode(['a' => ['b' => ['c' => 1]]]);
        $after  = json_encode(['a' => ['b' => ['c' => 2]]]);

        $changes = $this->differ->diff($before, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('a.b.c', $changes[0]['path']);
    }

    /**
     * Null before string treats the missing side as an empty array.
     */
    public function testNullBeforeIsEmptyObject(): void {
        $after = json_encode(['foo' => 'bar']);

        $changes = $this->differ->diff(null, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('added', $changes[0]['type']);
        $this->assertSame('foo',   $changes[0]['path']);
    }

    /**
     * Null after string treats the missing side as an empty array.
     */
    public function testNullAfterIsEmptyObject(): void {
        $before = json_encode(['foo' => 'bar']);

        $changes = $this->differ->diff($before, null);

        $this->assertCount(1, $changes);
        $this->assertSame('removed', $changes[0]['type']);
        $this->assertSame('foo',     $changes[0]['path']);
    }

    /**
     * Both sides null produces no changes.
     */
    public function testBothNullProducesNoChanges(): void {
        $changes = $this->differ->diff(null, null);

        $this->assertSame([], $changes);
    }

    /**
     * Invalid JSON on either side is treated as an empty object.
     */
    public function testInvalidJsonTreatedAsEmpty(): void {
        $changes = $this->differ->diff('not json', json_encode(['foo' => 1]));

        $this->assertCount(1, $changes);
        $this->assertSame('added', $changes[0]['type']);
    }

    /**
     * Type changes (e.g. string → int) are reported as changed.
     */
    public function testTypeChange(): void {
        $before = json_encode(['count' => '5']);
        $after  = json_encode(['count' => 5]);

        $changes = $this->differ->diff($before, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('changed', $changes[0]['type']);
        $this->assertSame('count',   $changes[0]['path']);
    }

    /**
     * When an array becomes a scalar the change is reported at the parent path.
     *
     * @return array<int, array<int, string>>
     */
    public static function provideScalarTypes(): array {
        return [
            ['before' => json_encode(['x' => true]),  'after' => json_encode(['x' => false])],
            ['before' => json_encode(['x' => 0]),     'after' => json_encode(['x' => 1])],
            ['before' => json_encode(['x' => 1.0]),   'after' => json_encode(['x' => 2.0])],
            ['before' => json_encode(['x' => 'foo']), 'after' => json_encode(['x' => 'bar'])],
        ];
    }

    /**
     * Changed scalar values of all supported types are detected.
     */
    #[DataProvider('provideScalarTypes')]
    public function testScalarTypeChanges(string $before, string $after): void {
        $changes = $this->differ->diff($before, $after);

        $this->assertCount(1, $changes);
        $this->assertSame('changed', $changes[0]['type']);
    }

    /**
     * Two empty JSON objects produce no changes.
     */
    public function testBothEmptyObjects(): void {
        $changes = $this->differ->diff('{}', '{}');

        $this->assertSame([], $changes);
    }
}
