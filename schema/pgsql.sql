CREATE TABLE sources (
    source_id  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name       VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT NULL,
    UNIQUE (name)
);

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;

CREATE TRIGGER sources_updated_at
    BEFORE UPDATE ON sources
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE types (
    type_id    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    source_id  BIGINT NOT NULL,
    name       VARCHAR(255) NOT NULL,
    plugin     VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT NULL,
    UNIQUE (source_id, name)
);

CREATE TRIGGER types_updated_at
    BEFORE UPDATE ON types
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE sessions (
    session_id VARCHAR(128) NOT NULL,
    data       TEXT,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (session_id)
);

CREATE INDEX sessions_updated_at ON sessions (updated_at);

CREATE TRIGGER sessions_updated_at
    BEFORE UPDATE ON sessions
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE api_keys (
    api_key_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    key_hash   VARCHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP DEFAULT NULL,
    UNIQUE (key_hash)
);

CREATE TABLE users (
    user_id       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    name          VARCHAR(255) DEFAULT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    google_id     VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP DEFAULT NULL,
    UNIQUE (email),
    UNIQUE (google_id)
);

CREATE TABLE logs (
    log_id      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    type_id     BIGINT NOT NULL,
    action      TEXT NOT NULL DEFAULT 'create' CHECK (action IN ('create', 'update', 'delete')),
    object_id   VARCHAR(255) NOT NULL,
    version     VARCHAR(255) DEFAULT NULL,
    data        TEXT,
    change_date TIMESTAMP NOT NULL,
    updated_by  VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX object_diffs ON logs (object_id, change_date);
