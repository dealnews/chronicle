<?php

namespace DealNews\Chronicle\Tests\Plugins;

use DealNews\Chronicle\Plugins\AbstractPlugin;
use DealNews\Chronicle\Plugins\DatoCMS;
use DealNews\Chronicle\Plugins\JsonPath;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AbstractPlugin static discovery and resolution methods.
 *
 * @package DealNews\Chronicle\Tests
 */
class AbstractPluginTest extends TestCase {

    // ─── getAvailable ────────────────────────────────────────────────────────

    public function testGetAvailableIncludesDatoCMS(): void {
        $plugins = AbstractPlugin::getAvailable();

        $this->assertArrayHasKey('DatoCMS', $plugins);
        $this->assertSame(DatoCMS::DESCRIPTION, $plugins['DatoCMS']);
    }

    public function testGetAvailableIncludesJsonPath(): void {
        $plugins = AbstractPlugin::getAvailable();

        $this->assertArrayHasKey('JsonPath', $plugins);
        $this->assertSame(JsonPath::DESCRIPTION, $plugins['JsonPath']);
    }

    public function testGetAvailableExcludesAbstractPlugin(): void {
        $plugins = AbstractPlugin::getAvailable();

        $this->assertArrayNotHasKey('AbstractPlugin', $plugins);
    }

    public function testGetAvailableReturnsSortedKeys(): void {
        $plugins = AbstractPlugin::getAvailable();
        $keys    = array_keys($plugins);

        $sorted = $keys;
        sort($sorted);

        $this->assertSame($sorted, $keys);
    }

    // ─── resolve ─────────────────────────────────────────────────────────────

    public function testResolveBuiltInByShortName(): void {
        $class = AbstractPlugin::resolve('DatoCMS');

        $this->assertSame(DatoCMS::class, $class);
    }

    public function testResolveJsonPathByShortName(): void {
        $class = AbstractPlugin::resolve('JsonPath');

        $this->assertSame(JsonPath::class, $class);
    }

    public function testResolveReturnsNullForUnknownShortName(): void {
        $class = AbstractPlugin::resolve('NonExistentPlugin');

        $this->assertNull($class);
    }

    public function testResolveReturnsNullForAbstractPlugin(): void {
        $class = AbstractPlugin::resolve('AbstractPlugin');

        $this->assertNull($class);
    }

    public function testResolveReturnsNullForEmptyString(): void {
        $class = AbstractPlugin::resolve('');

        $this->assertNull($class);
    }

    public function testResolveReturnsNullForNonPluginClass(): void {
        $class = AbstractPlugin::resolve(\stdClass::class);

        $this->assertNull($class);
    }
}
