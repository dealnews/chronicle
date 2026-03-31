PRAGMA foreign_keys = ON;

CREATE TABLE chronicle_sources (
    source_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    updated_at  TEXT DEFAULT NULL,
    UNIQUE (name)
);

CREATE TRIGGER chronicle_sources_updated_at
    AFTER UPDATE ON chronicle_sources
    FOR EACH ROW BEGIN
        UPDATE chronicle_sources SET updated_at = strftime('%Y-%m-%d %H:%M:%S', 'now')
        WHERE source_id = OLD.source_id;
    END;

CREATE TABLE chronicle_types (
    type_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    source_id   INTEGER NOT NULL,
    name        TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    plugin      TEXT DEFAULT NULL,
    created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    updated_at  TEXT DEFAULT NULL,
    UNIQUE (source_id, name)
);

CREATE TRIGGER chronicle_types_updated_at
    AFTER UPDATE ON chronicle_types
    FOR EACH ROW BEGIN
        UPDATE chronicle_types SET updated_at = strftime('%Y-%m-%d %H:%M:%S', 'now')
        WHERE type_id = OLD.type_id;
    END;

CREATE TABLE chronicle_sessions (
    session_id TEXT NOT NULL,
    data       TEXT,
    updated_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    PRIMARY KEY (session_id)
);

CREATE INDEX chronicle_sessions_updated_at ON chronicle_sessions (updated_at);

CREATE TRIGGER chronicle_sessions_updated_at
    AFTER UPDATE ON chronicle_sessions
    FOR EACH ROW BEGIN
        UPDATE chronicle_sessions SET updated_at = strftime('%Y-%m-%d %H:%M:%S', 'now')
        WHERE session_id = OLD.session_id;
    END;

CREATE TABLE chronicle_api_keys (
    api_key_id INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    key_hash   TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    revoked_at TEXT DEFAULT NULL,
    UNIQUE (key_hash)
);

CREATE TABLE chronicle_users (
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

CREATE TABLE chronicle_logs (
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

CREATE INDEX object_diffs ON chronicle_logs (type_id, object_id, change_date);
