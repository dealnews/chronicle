PRAGMA foreign_keys = ON;

CREATE TABLE sources (
    source_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    updated_at  TEXT DEFAULT NULL,
    UNIQUE (name)
);

CREATE TRIGGER sources_updated_at
    AFTER UPDATE ON sources
    FOR EACH ROW BEGIN
        UPDATE sources SET updated_at = strftime('%Y-%m-%d %H:%M:%S', 'now')
        WHERE source_id = OLD.source_id;
    END;

CREATE TABLE types (
    type_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    source_id   INTEGER NOT NULL,
    name        TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    plugin      TEXT DEFAULT NULL,
    created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    updated_at  TEXT DEFAULT NULL,
    UNIQUE (source_id, name)
);

CREATE TRIGGER types_updated_at
    AFTER UPDATE ON types
    FOR EACH ROW BEGIN
        UPDATE types SET updated_at = strftime('%Y-%m-%d %H:%M:%S', 'now')
        WHERE type_id = OLD.type_id;
    END;

CREATE TABLE sessions (
    session_id TEXT NOT NULL,
    data       TEXT,
    updated_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    PRIMARY KEY (session_id)
);

CREATE INDEX sessions_updated_at ON sessions (updated_at);

CREATE TRIGGER sessions_updated_at
    AFTER UPDATE ON sessions
    FOR EACH ROW BEGIN
        UPDATE sessions SET updated_at = strftime('%Y-%m-%d %H:%M:%S', 'now')
        WHERE session_id = OLD.session_id;
    END;

CREATE TABLE api_keys (
    api_key_id INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    key_hash   TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    revoked_at TEXT DEFAULT NULL,
    UNIQUE (key_hash)
);

CREATE TABLE users (
    user_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    email         TEXT NOT NULL,
    name          TEXT DEFAULT NULL,
    password_hash TEXT DEFAULT NULL,
    google_id     TEXT DEFAULT NULL,
    created_at    TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    last_login_at TEXT DEFAULT NULL,
    UNIQUE (email),
    UNIQUE (google_id)
);

CREATE TABLE logs (
    log_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    type_id     INTEGER NOT NULL,
    action      TEXT NOT NULL DEFAULT 'create' CHECK (action IN ('create', 'update', 'delete')),
    object_id   TEXT NOT NULL,
    version     TEXT DEFAULT NULL,
    data        TEXT,
    change_date TEXT NOT NULL,
    updated_by  TEXT DEFAULT NULL,
    created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now'))
);

CREATE INDEX object_diffs ON logs (type_id, object_id, change_date);
