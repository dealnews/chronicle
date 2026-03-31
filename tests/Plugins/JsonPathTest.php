<?php

namespace DealNews\Chronicle\Tests\Plugins;

use DealNews\Chronicle\Plugins\JsonPath;
use DealNews\GetConfig\GetConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the JSONPath config-driven webhook plugin.
 *
 * Each test injects a mock GetConfig so no real ini file is needed.
 * The fixture is the same DatoCMS payload used by DatoCMSTest.
 *
 * @package DealNews\Chronicle\Tests
 */
class JsonPathTest extends TestCase {

    protected string $fixture;

    /**
     * Config key prefix used by all helpers below.
     * Matches source="dato" and type="brand".
     */
    protected string $prefix = 'chronicle.plugin.dato.brand';

    protected function setUp(): void {
        $this->fixture = file_get_contents(__DIR__ . '/../fixtures/datocms.json');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Builds a mock GetConfig that returns values from the supplied map.
     * Keys not present in the map return null.
     *
     * @param  array<string, string|null> $map
     * @return GetConfig&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockConfig(array $map): GetConfig {
        $config = $this->createStub(GetConfig::class);

        $config->method('get')
               ->willReturnCallback(function (string $key) use ($map): ?string {
                   return $map[$key] ?? null;
               });

        return $config;
    }

    /**
     * Returns a plugin wired with the minimum required config keys so that
     * additional keys can be layered on top per test.
     *
     * @param  array<string, string|null> $extra  Keys to merge over the defaults.
     * @return JsonPath
     */
    protected function makePlugin(array $extra = []): JsonPath {
        $defaults = [
            "{$this->prefix}.object_id"   => '$.entity.id',
            "{$this->prefix}.action"      => '$.event_type',
            "{$this->prefix}.change_date" => '$.event_triggered_at',
        ];

        return new JsonPath(
            $this->fixture,
            'dato',
            'brand',
            $this->mockConfig(array_merge($defaults, $extra))
        );
    }

    // ─── Required fields ─────────────────────────────────────────────────────

    public function testGetObjectId(): void {
        $plugin = $this->makePlugin();

        $this->assertSame('N479d1CxTHSglNDxVSrICg', $plugin->getObjectId());
    }

    public function testGetChangeDate(): void {
        $plugin = $this->makePlugin();

        $this->assertSame('2026-03-30T19:12:08Z', $plugin->getChangeDate());
    }

    public function testGetObjectIdThrowsWhenConfigKeyMissing(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/object_id/');

        $plugin = new JsonPath(
            $this->fixture,
            'dato',
            'brand',
            $this->mockConfig([
                "{$this->prefix}.action"      => '$.event_type',
                "{$this->prefix}.change_date" => '$.event_triggered_at',
                // object_id intentionally absent
            ])
        );

        $plugin->getObjectId();
    }

    public function testGetChangeDateThrowsWhenPathMatchesNothing(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/returned no result/');

        $plugin = $this->makePlugin([
            "{$this->prefix}.change_date" => '$.nonexistent.path',
        ]);

        $plugin->getChangeDate();
    }

    // ─── version ─────────────────────────────────────────────────────────────

    public function testGetVersionReturnsValue(): void {
        $plugin = $this->makePlugin([
            "{$this->prefix}.version" => '$.entity.meta.current_version',
        ]);

        $this->assertSame('Yq8xFAcuTcWR026M4X7Tow', $plugin->getVersion());
    }

    public function testGetVersionNullWhenNoConfigKey(): void {
        $plugin = $this->makePlugin();

        $this->assertNull($plugin->getVersion());
    }

    public function testGetVersionNullWhenPathMatchesNothing(): void {
        $plugin = $this->makePlugin([
            "{$this->prefix}.version" => '$.entity.meta.nonexistent',
        ]);

        $this->assertNull($plugin->getVersion());
    }

    // ─── changed_by ──────────────────────────────────────────────────────────

    public function testGetChangedByNullWhenNoConfigKey(): void {
        $plugin = $this->makePlugin();

        $this->assertNull($plugin->getChangedBy());
    }

    public function testGetChangedByNullWhenPathMatchesNothing(): void {
        $plugin = $this->makePlugin([
            "{$this->prefix}.changed_by" => '$.editor.email',
        ]);

        $this->assertNull($plugin->getChangedBy());
    }

    public function testGetChangedByReturnsValue(): void {
        $payload = json_decode($this->fixture, true);
        $payload['editor'] = ['email' => 'alice@example.com'];

        $plugin = new JsonPath(
            json_encode($payload),
            'dato',
            'brand',
            $this->mockConfig([
                "{$this->prefix}.object_id"   => '$.entity.id',
                "{$this->prefix}.action"      => '$.event_type',
                "{$this->prefix}.change_date" => '$.event_triggered_at',
                "{$this->prefix}.changed_by"  => '$.editor.email',
            ])
        );

        $this->assertSame('alice@example.com', $plugin->getChangedBy());
    }

    // ─── action ──────────────────────────────────────────────────────────────

    public function testGetActionReturnsRawValueWhenNoMappingKeys(): void {
        $plugin = $this->makePlugin();

        // Fixture event_type is "publish"; no *_actions keys are configured
        $this->assertSame('publish', $plugin->getAction());
    }

    public function testGetActionMappedToCreate(): void {
        $plugin = $this->makePlugin([
            "{$this->prefix}.create_actions" => 'create,publish',
        ]);

        // "publish" should map to "create"
        $this->assertSame('create', $plugin->getAction());
    }

    public function testGetActionMappedToUpdate(): void {
        $payload = json_decode($this->fixture, true);
        $payload['event_type'] = 'save';

        $plugin = new JsonPath(
            json_encode($payload),
            'dato',
            'brand',
            $this->mockConfig([
                "{$this->prefix}.object_id"      => '$.entity.id',
                "{$this->prefix}.action"         => '$.event_type',
                "{$this->prefix}.change_date"    => '$.event_triggered_at',
                "{$this->prefix}.update_actions" => 'update,save',
            ])
        );

        $this->assertSame('update', $plugin->getAction());
    }

    public function testGetActionMappedToDelete(): void {
        $payload = json_decode($this->fixture, true);
        $payload['event_type'] = 'unpublish';

        $plugin = new JsonPath(
            json_encode($payload),
            'dato',
            'brand',
            $this->mockConfig([
                "{$this->prefix}.object_id"      => '$.entity.id',
                "{$this->prefix}.action"         => '$.event_type',
                "{$this->prefix}.change_date"    => '$.event_triggered_at',
                "{$this->prefix}.delete_actions" => 'delete,unpublish,archive',
            ])
        );

        $this->assertSame('delete', $plugin->getAction());
    }

    // ─── data ─────────────────────────────────────────────────────────────────

    public function testGetDataReturnsExtractedSubtreeWhenPathConfigured(): void {
        $plugin = $this->makePlugin([
            "{$this->prefix}.data" => '$.entity',
        ]);

        $data = $plugin->getData();

        $this->assertIsArray($data);
        $this->assertSame('N479d1CxTHSglNDxVSrICg', $data['id']);
        $this->assertSame('item', $data['type']);
        $this->assertArrayHasKey('attributes', $data);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testGetDataReturnsFullPayloadWhenNoPathConfigured(): void {
        $plugin = $this->makePlugin();

        $data = $plugin->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('entity', $data);
        $this->assertArrayHasKey('event_type', $data);
        $this->assertArrayHasKey('event_triggered_at', $data);
    }

    public function testGetDataWrapsScalarInArray(): void {
        $plugin = $this->makePlugin([
            "{$this->prefix}.data" => '$.event_type',
        ]);

        $data = $plugin->getData();

        $this->assertSame(['publish'], $data);
    }

    // ─── Metadata ─────────────────────────────────────────────────────────────

    public function testDescriptionConstantIsDefined(): void {
        $this->assertTrue(defined(JsonPath::class . '::DESCRIPTION'));
        $this->assertNotEmpty(JsonPath::DESCRIPTION);
    }
}
