<?php

namespace DealNews\Chronicle\Service;

use DealNews\DB\CRUD;
use SessionHandlerInterface;

/**
 * Database-backed PHP session handler.
 *
 * Stores sessions in the chronicle.sessions table so the application
 * can run on multiple hosts simultaneously without shared filesystem state.
 *
 * Register before any session_start() call:
 *
 *     $handler = new SessionHandler();
 *     session_set_save_handler($handler, true);
 *
 * @package DealNews\Chronicle
 */
class SessionHandler implements SessionHandlerInterface {

    /**
     * Database table name.
     */
    protected const TABLE = 'sessions';

    /**
     * @var CRUD
     */
    protected CRUD $crud;

    /**
     * Initialises the CRUD connection to the chronicle database.
     *
     * @param CRUD|null $crud Optional CRUD instance (for testing).
     */
    public function __construct(?CRUD $crud = null) {
        $this->crud = $crud ?? CRUD::factory('chronicle');
    }

    /**
     * @param  string $path Ignored — path is determined by DB config.
     * @param  string $name Ignored.
     * @return bool
     */
    public function open(string $path, string $name): bool {
        return true;
    }

    /**
     * @return bool
     */
    public function close(): bool {
        return true;
    }

    /**
     * Returns serialised session data for the given session ID.
     *
     * @param  string $id
     * @return string|false Empty string if not found, false on error.
     */
    public function read(string $id): string|false {
        $rows = $this->crud->read(self::TABLE, ['session_id' => $id], 1);

        if (empty($rows)) {
            return '';
        }

        return (string) reset($rows)['data'];
    }

    /**
     * Writes (upserts) session data to the database.
     *
     * @param  string $id
     * @param  string $data Serialised session data.
     * @return bool
     */
    public function write(string $id, string $data): bool {
        $existing = $this->crud->read(self::TABLE, ['session_id' => $id], 1);

        if (!empty($existing)) {
            return $this->crud->update(
                self::TABLE,
                ['data' => $data],
                ['session_id' => $id]
            );
        }

        return $this->crud->create(self::TABLE, [
            'session_id' => $id,
            'data'       => $data,
        ]);
    }

    /**
     * Deletes a session record.
     *
     * @param  string $id
     * @return bool
     */
    public function destroy(string $id): bool {
        return $this->crud->delete(self::TABLE, ['session_id' => $id]);
    }

    /**
     * Removes sessions that have not been updated within $max_lifetime seconds.
     *
     * @param  int $max_lifetime
     * @return int|false Number of sessions deleted, or false on error.
     */
    public function gc(int $max_lifetime): int|false {
        $expires = date('Y-m-d H:i:s', time() - $max_lifetime);

        $sth = $this->crud->run(
            'DELETE FROM sessions WHERE updated_at < :expires',
            [':expires' => $expires]
        );

        return $sth->rowCount();
    }
}
