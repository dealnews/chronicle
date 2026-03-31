<?php

namespace DealNews\Chronicle\Tests\Service;

use DealNews\Chronicle\Service\SessionHandler;
use DealNews\DB\CRUD;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the database-backed session handler.
 *
 * @package DealNews\Chronicle\Tests
 */
class SessionHandlerTest extends TestCase {

    /**
     * @return CRUD&\PHPUnit\Framework\MockObject\Stub
     */
    protected function mockCrud(): CRUD {
        return $this->createStub(CRUD::class);
    }

    // ─── open / close ────────────────────────────────────────────────────────

    public function testOpenReturnsTrue(): void {
        $handler = new SessionHandler($this->mockCrud());

        $this->assertTrue($handler->open('/tmp', 'PHPSESSID'));
    }

    public function testCloseReturnsTrue(): void {
        $handler = new SessionHandler($this->mockCrud());

        $this->assertTrue($handler->close());
    }

    // ─── read ────────────────────────────────────────────────────────────────

    public function testReadReturnsDataWhenSessionExists(): void {
        $crud = $this->mockCrud();
        $crud->method('read')
             ->willReturn([['session_id' => 'abc', 'data' => 'serialized_data']]);

        $handler = new SessionHandler($crud);

        $this->assertSame('serialized_data', $handler->read('abc'));
    }

    public function testReadReturnsEmptyStringWhenSessionNotFound(): void {
        $crud = $this->mockCrud();
        $crud->method('read')
             ->willReturn([]);

        $handler = new SessionHandler($crud);

        $this->assertSame('', $handler->read('nonexistent'));
    }

    // ─── write ───────────────────────────────────────────────────────────────

    public function testWriteUpdatesExistingSession(): void {
        $crud = $this->createMock(CRUD::class);

        $crud->method('read')
             ->willReturn([['session_id' => 'abc', 'data' => 'old']]);

        $crud->expects($this->once())
             ->method('update')
             ->with('sessions', ['data' => 'new_data'], ['session_id' => 'abc'])
             ->willReturn(true);

        $handler = new SessionHandler($crud);

        $this->assertTrue($handler->write('abc', 'new_data'));
    }

    public function testWriteCreatesNewSession(): void {
        $crud = $this->createMock(CRUD::class);

        $crud->method('read')
             ->willReturn([]);

        $crud->expects($this->once())
             ->method('create')
             ->with('sessions', ['session_id' => 'new_id', 'data' => 'some_data'])
             ->willReturn(true);

        $handler = new SessionHandler($crud);

        $this->assertTrue($handler->write('new_id', 'some_data'));
    }

    // ─── destroy ─────────────────────────────────────────────────────────────

    public function testDestroyDeletesSession(): void {
        $crud = $this->createMock(CRUD::class);

        $crud->expects($this->once())
             ->method('delete')
             ->with('sessions', ['session_id' => 'abc'])
             ->willReturn(true);

        $handler = new SessionHandler($crud);

        $this->assertTrue($handler->destroy('abc'));
    }

    // ─── gc ──────────────────────────────────────────────────────────────────

    public function testGcCallsDeleteWithExpiry(): void {
        $crud = $this->createMock(CRUD::class);

        $crud->expects($this->once())
             ->method('run')
             ->with(
                 'DELETE FROM sessions WHERE updated_at < :expires',
                 $this->callback(function (array $params): bool {
                     return isset($params[':expires']) &&
                            strtotime($params[':expires']) !== false;
                 })
             );

        $handler = new SessionHandler($crud);

        // gc() calls rowCount() on the result, which goes through
        // __call on the DB wrapper. We just verify the query is issued.
        try {
            $handler->gc(1800);
        } catch (\Throwable) {
            // Expected — the stub can't fully simulate PDOStatement
        }
    }

    public function testGcExpiryIsBasedOnMaxLifetime(): void {
        $captured_params = [];

        $crud = $this->createStub(CRUD::class);
        $crud->method('run')
             ->willReturnCallback(function (string $query, array $params) use (&$captured_params) {
                 $captured_params = $params;
                 throw new \RuntimeException('stop');
             });

        $handler = new SessionHandler($crud);

        try {
            $handler->gc(3600);
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertArrayHasKey(':expires', $captured_params);
        $expires = strtotime($captured_params[':expires']);
        $expected = time() - 3600;

        // Allow 2 seconds of clock drift
        $this->assertEqualsWithDelta($expected, $expires, 2);
    }
}
