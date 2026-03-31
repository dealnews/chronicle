<img src="icon.svg" alt="Chronicle Icon" width="120" height="120">

# Chronicle

![PHP](https://img.shields.io/badge/PHP-8.4%2B-blue)
![License](https://img.shields.io/badge/license-BSD--3--Clause-green)

Chronicle is a webhook-driven object history tracker. It ingests JSON payloads from external systems, parses them through a plugin layer, and stores a versioned log of every change to every object. A built-in web UI lets you browse sources, types, and the full diff history of any object.

## Table of Contents

- [How It Works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Running the App](#running-the-app)
- [Running with Docker](#running-with-docker)
- [Ingesting Webhooks](#ingesting-webhooks)
- [Plugin System](#plugin-system)
- [Admin UI](#admin-ui)
- [Browsing History](#browsing-history)
- [Authentication](#authentication)
- [Managing Log Table Size](#managing-log-table-size)
- [Testing](#testing)
- [Project Structure](#project-structure)

## How It Works

1. An external system POSTs a JSON payload to `/webhook/{source}/{type}` with a Bearer API key.
2. Chronicle validates the key, looks up the source and type, and instantiates the configured plugin.
3. The plugin extracts the object ID, action (create/update/delete), change date, version, and data from the payload.
4. A log entry is persisted to the database.
5. The web UI shows the full chronological history for any object, with consecutive versions diffed side by side.

## Requirements

- PHP 8.4+
- MySQL, PostgreSQL, or SQLite
- Composer

## Installation

```bash
git clone https://github.com/dealnews/chronicle.git
cd chronicle
composer install
```

## Database Setup

Schema files are provided for all three supported databases:

```bash
# MySQL
mysql -u user -p dbname < schema/mysql.sql

# PostgreSQL
psql -U user -d dbname -f schema/pgsql.sql

# SQLite
sqlite3 path/to/dev.db < schema/sqlite.sql
```

The schema creates six tables: `chronicle_sources`, `chronicle_types`, `chronicle_logs`, `chronicle_users`, `chronicle_api_keys`, and `chronicle_sessions`.

## Configuration

Copy or create `etc/config.ini` with your database connection. The config key name is `db.chronicle`:

```ini
db.chronicle.type   = mysql
db.chronicle.server = localhost
db.chronicle.db     = chronicle
db.chronicle.user   = your_user
db.chronicle.pass   = your_password
db.chronicle.port   = 3306
```

For SQLite (useful in development):

```ini
db.chronicle.type = pdo
db.chronicle.dsn  = "sqlite:/path/to/dev.db"
```

### Google OAuth (optional)

To enable Google login, add:

```ini
chronicle.google.client_id      = your-client-id
chronicle.google.client_secret  = your-client-secret
chronicle.google.redirect_uri   = https://your-app/auth/callback

; Optional: restrict login to specific domains (comma-separated)
chronicle.google.allowed_domains = dealnews.com,example.com
```

When `allowed_domains` is set, any Google account whose email domain is not in the list will be rejected after authentication. When the key is absent, all Google accounts are permitted.

### External Plugins (optional)

Register plugins outside the built-in `src/Plugins/` directory:

```ini
chronicle.plugins = \My\Namespace\MyPlugin,\Another\Plugin
```

### JSONPath Plugin Config

The generic [JSONPath plugin](#jsonpath-plugin) is configured entirely through `config.ini`. Keys are scoped per source and type:

```ini
chronicle.plugin.{source}.{type}.object_id      = $.entity.id
chronicle.plugin.{source}.{type}.action         = $.event_type
chronicle.plugin.{source}.{type}.change_date    = $.event_triggered_at
chronicle.plugin.{source}.{type}.version        = $.entity.meta.current_version
chronicle.plugin.{source}.{type}.data           = $.entity
chronicle.plugin.{source}.{type}.changed_by     = $.editor.email

; Map raw event values to canonical actions
chronicle.plugin.{source}.{type}.create_actions = create,publish
chronicle.plugin.{source}.{type}.update_actions = update,save
chronicle.plugin.{source}.{type}.delete_actions = delete,unpublish,archive
```

`object_id`, `action`, and `change_date` are required. The rest are optional.

## Running the App

Chronicle is a standard PHP web application. Point your web server's document root at the `public/` directory.

**PHP built-in server (development):**

```bash
php -S localhost:8001 -t public public/index.php
```

## Running with Docker

The official image is [`dealnews/chronicle`](https://hub.docker.com/r/dealnews/chronicle) on Docker Hub. The application listens on port 80 inside the container and expects `config.ini` to be provided at `/app/etc/config.ini`.

**docker run:**

```bash
docker run -d \
  -p 8000:80 \
  -v /path/to/your/config.ini:/app/etc/config.ini:ro \
  dealnews/chronicle:latest
```

**Docker Compose:**

A `docker-compose.yml` is included at the root of this repository:

```yaml
services:
  chronicle:
    image: dealnews/chronicle:latest
    ports:
      - "8000:80"
    volumes:
      - ./etc/config.ini:/app/etc/config.ini:ro
```

Copy `etc/config.example.ini` to `etc/config.ini`, fill in your database connection details, then start the container:

```bash
docker compose up -d
```

The application will be available at `http://localhost:8000`.

## Ingesting Webhooks

Send a POST request to `/webhook/{source}/{type}` with:

- `Authorization: Bearer <api-key>` header
- `Content-Type: application/json` header
- A JSON payload in the body

```bash
curl -X POST http://localhost:8001/webhook/dato-item/brands \
  -H "Authorization: Bearer your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"event_type":"update","event_triggered_at":"2024-01-15T10:30:00Z","entity":{"id":"abc123","meta":{"current_version":"v5"},"name":"Acme"}}'
```

A successful response returns HTTP 201 with the new log ID:

```json
{"log_id": 42}
```

### Error Responses

| Status | Reason |
|--------|--------|
| 401 | Missing, invalid, or revoked API key |
| 400 | Missing or invalid JSON body; plugin config error |
| 404 | Source or type not found in the database |
| 500 | Plugin could not be resolved or threw unexpectedly |

## Plugin System

A plugin translates a raw JSON webhook payload into the fields Chronicle stores: `object_id`, `action`, `change_date`, `version`, `changed_by`, and `data`.

### Built-in Plugins

**DatoCMS** — Hardcoded for DatoCMS webhook envelopes. Expects:

```json
{
  "event_type": "create",
  "event_triggered_at": "2024-01-01T12:00:00Z",
  "entity": {
    "id": "abc123",
    "meta": { "current_version": "v1" }
  }
}
```

**JSONPath (config-driven)** — Uses JSONPath expressions from `config.ini` to extract fields from any JSON payload. See [JSONPath Plugin Config](#jsonpath-plugin-config) above.

### Writing a Custom Plugin

1. Create a class that extends `DealNews\Chronicle\Plugins\AbstractPlugin`.
2. Declare `public const DESCRIPTION = 'My Plugin';`.
3. Implement all six abstract methods: `getData()`, `getChangeDate()`, `getChangedBy()`, `getObjectId()`, `getAction()`, `getVersion()`.
4. Place it in `src/Plugins/` (auto-discovered) or register it via `chronicle.plugins` in config.

```php
<?php

namespace DealNews\Chronicle\Plugins;

class MyPlugin extends AbstractPlugin {

    public const DESCRIPTION = 'My Custom Plugin';

    public function getObjectId(): string {
        return $this->payload['id'];
    }

    public function getAction(): ?string {
        return $this->payload['action'] ?? 'update';
    }

    public function getChangeDate(): string {
        return $this->payload['timestamp'];
    }

    public function getVersion(): ?string {
        return $this->payload['version'] ?? null;
    }

    public function getChangedBy(): ?string {
        return $this->payload['user'] ?? null;
    }

    public function getData(): array {
        return $this->payload;
    }
}
```

## Admin UI

The admin UI is available at `/admin/` (requires login).

| Section | Path | Description |
|---------|------|-------------|
| Sources | `/admin/sources` | Create and manage sources (e.g. `dato-item`) |
| Types | `/admin/types` | Create and manage types within a source; assign a plugin |
| API Keys | `/admin/api-keys` | Generate and revoke webhook API keys |

**Sources** are top-level groupings (e.g. the name of an external system).
**Types** are sub-groupings within a source (e.g. the content model or entity type). Each type must have a plugin assigned before it can accept webhooks.

## Browsing History

The history UI requires a logged-in session.

| Path | Description |
|------|-------------|
| `/` | List all sources |
| `/{source}` | List all types for a source |
| `/{source}/{type}` | Look up an object by ID |
| `/{source}/{type}/{object_id}` | Full version history for an object, with diffs |

On the object history page, all versions are shown chronologically. If a system fires a `create` and `update` event simultaneously, the `create` entry is always sorted to the top so it appears as the initial version when diffing.

## Authentication

### First Run

On first launch, if no users exist in the database, Chronicle presents a one-time setup form to create the initial admin account with email and password.

### Login Methods

- **Email/password** — `/auth/login`
- **Google OAuth** — `/auth/google` (requires Google OAuth config keys)

## Managing Log Table Size

Chronicle writes a row to the `chronicle_logs` table for every webhook event it receives and never deletes or archives anything on its own. As event volume grows, the table can become very large. Managing its size is entirely the responsibility of the operator.

The approaches below are common strategies. For production use, consult your database's official documentation before implementing any of them.

### DELETE-based cron job

The simplest approach: run a scheduled job that deletes rows older than a retention window.

```sql
DELETE FROM chronicle_logs WHERE change_date < NOW() - INTERVAL 1 YEAR;
```

This works on all three supported databases (substitute `INTERVAL '1 year'` on PostgreSQL and SQLite). On large tables, `DELETE` can be slow and leaves behind dead rows that require a subsequent `VACUUM` (PostgreSQL) or `OPTIMIZE TABLE` (MySQL). Run during low-traffic periods and consider deleting in batches to reduce lock contention.

A variant is to delete by version count rather than age — keeping only the most recent N versions per object. Chronicle has no built-in concept of object existence, so age-based retention is generally simpler.

### MySQL table partitioning

MySQL supports `RANGE` partitioning, which lets you drop an entire partition (e.g. one year's worth of rows) with a single fast metadata operation instead of a slow row-by-row `DELETE`.

The `chronicle_logs` table must be created as a partitioned table from the start. A yearly partition on `change_date` looks like:

```sql
CREATE TABLE `chronicle_logs` (
    `log_id`      bigint unsigned NOT NULL AUTO_INCREMENT,
    `type_id`     bigint unsigned NOT NULL,
    `action`      enum('create','update','delete') NOT NULL DEFAULT 'create',
    `object_id`   varchar(255) NOT NULL,
    `version`     varchar(255) DEFAULT NULL,
    `data`        longtext,
    `change_date` datetime NOT NULL,
    `updated_by`  varchar(255) DEFAULT NULL,
    `created_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`, `change_date`),
    KEY `object_diffs` (`type_id`, `object_id`, `change_date`)
)
PARTITION BY RANGE COLUMNS(`change_date`) (
    PARTITION p2025 VALUES LESS THAN ('2026-01-01')
);
```

> **Note:** MySQL requires the partition key to be part of the primary key, which is why `change_date` is added to the `PRIMARY KEY` above.

To drop a partition and all its rows instantly:

```sql
ALTER TABLE `chronicle_logs` DROP PARTITION p2025;
```

For full details on creating, adding, and dropping partitions see the [MySQL Partitioning documentation](https://dev.mysql.com/doc/refman/8.4/en/partitioning.html).

### PostgreSQL table partitioning

PostgreSQL 10+ supports declarative range partitioning. Like MySQL, this is a schema-time decision — you cannot partition an existing regular table in place without recreating it.

```sql
CREATE TABLE chronicle_logs (
    log_id      BIGINT GENERATED ALWAYS AS IDENTITY,
    type_id     BIGINT NOT NULL,
    action      TEXT NOT NULL DEFAULT 'create' CHECK (action IN ('create', 'update', 'delete')),
    object_id   VARCHAR(255) NOT NULL,
    version     VARCHAR(255) DEFAULT NULL,
    data        TEXT,
    change_date TIMESTAMP NOT NULL,
    updated_by  VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id, change_date)
) PARTITION BY RANGE (change_date);

CREATE TABLE chronicle_logs_2025
    PARTITION OF chronicle_logs
    FOR VALUES FROM ('2025-01-01') TO ('2026-01-01');

CREATE INDEX object_diffs ON chronicle_logs (type_id, object_id, change_date);
```

Dropping a year's data is then a fast metadata operation:

```sql
DROP TABLE chronicle_logs_2025;
```

The **pg_partman** extension can automate partition creation and retention so you don't have to manage the DDL manually. For full details see the [PostgreSQL Table Partitioning documentation](https://www.postgresql.org/docs/current/ddl-partitioning.html).

## Testing

```bash
composer install
vendor/bin/phpunit
```

Tests live in `tests/` and mirror the `src/` directory structure. The test suite uses PHPUnit 12.

## Project Structure

```
.
├── etc/
│   └── config.ini          # Database and app configuration
├── public/
│   └── index.php           # Front controller / entry point
├── schema/
│   ├── mysql.sql
│   ├── pgsql.sql
│   └── sqlite.sql
├── src/
│   ├── Action/             # Request actions (webhook ingest, auth, admin saves)
│   ├── Controller/         # Route handlers
│   ├── Data/               # Value objects (Source, Type, Log, User, ApiKey)
│   ├── Mapper/             # Database mappers
│   ├── Model/              # Data-fetching models for views
│   ├── Plugins/            # Webhook payload plugins
│   │   ├── AbstractPlugin.php
│   │   ├── DatoCMS.php
│   │   └── JsonPath.php
│   ├── Responder/          # Wires model data to views
│   ├── Service/            # Shared services (differ, session handler)
│   └── View/               # HTML views
│       ├── Admin/
│       ├── Auth/
│       ├── History/
│       └── Webhook/
└── tests/                  # PHPUnit test suite
```

## License

BSD 3-Clause. See `LICENSE` for details.
