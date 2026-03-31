<?php

namespace DealNews\Chronicle\Tests\Plugins;

use DealNews\Chronicle\Plugins\DatoCMS;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the DatoCMS webhook payload plugin.
 *
 * @package DealNews\Chronicle\Tests
 */
class DatoCMSTest extends TestCase {

    protected string $fixture;

    protected function setUp(): void {
        $this->fixture = file_get_contents(__DIR__ . '/../fixtures/datocms.json');
    }

    public function testGetObjectId(): void {
        $plugin = new DatoCMS($this->fixture);

        $this->assertSame('N479d1CxTHSglNDxVSrICg', $plugin->getObjectId());
    }

    public function testGetChangeDate(): void {
        $plugin = new DatoCMS($this->fixture);

        $this->assertSame('2026-03-30T19:12:08Z', $plugin->getChangeDate());
    }

    public function testGetVersion(): void {
        $plugin = new DatoCMS($this->fixture);

        $this->assertSame('Yq8xFAcuTcWR026M4X7Tow', $plugin->getVersion());
    }

    public function testGetChangedByIsAlwaysNull(): void {
        $plugin = new DatoCMS($this->fixture);

        $this->assertNull($plugin->getChangedBy());
    }

    /**
     * "publish" is not a recognized event_type so it should map to "update".
     */
    public function testGetActionDefaultsToUpdate(): void {
        $plugin = new DatoCMS($this->fixture);

        $this->assertSame('update', $plugin->getAction());
    }

    public function testGetActionCreate(): void {
        $payload = json_decode($this->fixture, true);
        $payload['event_type'] = 'create';

        $plugin = new DatoCMS(json_encode($payload));

        $this->assertSame('create', $plugin->getAction());
    }

    public function testGetActionDelete(): void {
        $payload = json_decode($this->fixture, true);
        $payload['event_type'] = 'delete';

        $plugin = new DatoCMS(json_encode($payload));

        $this->assertSame('delete', $plugin->getAction());
    }

    public function testGetDataReturnsEntityObject(): void {
        $plugin = new DatoCMS($this->fixture);
        $data   = $plugin->getData();

        $this->assertIsArray($data);
        $this->assertSame('N479d1CxTHSglNDxVSrICg', $data['id']);
        $this->assertSame('item', $data['type']);
        $this->assertArrayHasKey('attributes', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('relationships', $data);
    }

    public function testGetDataDoesNotIncludeEnvelopeKeys(): void {
        $plugin = new DatoCMS($this->fixture);
        $data   = $plugin->getData();

        $this->assertArrayNotHasKey('event_type', $data);
        $this->assertArrayNotHasKey('event_triggered_at', $data);
        $this->assertArrayNotHasKey('related_entities', $data);
    }

    public function testGetVersionNullWhenMissing(): void {
        $payload = json_decode($this->fixture, true);
        unset($payload['entity']['meta']['current_version']);

        $plugin = new DatoCMS(json_encode($payload));

        $this->assertNull($plugin->getVersion());
    }

    public function testInvalidJsonThrows(): void {
        $this->expectException(\InvalidArgumentException::class);

        new DatoCMS('not valid json');
    }

    public function testDescriptionConstantIsDefined(): void {
        $this->assertTrue(defined(DatoCMS::class . '::DESCRIPTION'));
        $this->assertNotEmpty(DatoCMS::DESCRIPTION);
    }
}
