# Chronicle — Agent Context

This file gives AI coding assistants the context they need to work effectively in
this codebase. It covers architecture, conventions, and the non-obvious stuff that
doesn't fall out of reading a single file.

---

## Project Overview

Chronicle is a webhook-driven object history tracker. External systems POST JSON
payloads to `/webhook/{source}/{type}`; Chronicle parses them through a plugin
layer and stores a versioned log. A web UI lets anyone browse the full diff history
of any object.

**Key use cases:**
- Track every change to a DatoCMS record over time.
- Use the generic JSONPath plugin to track changes in *any* JSON-based system
  without writing PHP.
- Write a custom plugin for a source that needs bespoke parsing logic.

---

## Architecture & Data Flow

### Request lifecycle

```
index.php → Router → Controller → Action (optional) → Model → Responder → View
```

- **Router** (`public/index.php`) matches the URL and extracts route tokens into
  `$inputs`.
- **Controller** validates inputs, selects a Model, and delegates to a Responder.
  Controllers are thin — no business logic.
- **Action** handles side effects (writing to DB, validating API keys). Returned
  data is merged into the data array passed to the Responder.
- **Model** fetches read data from the DB and returns a plain array.
- **Responder** wires model data to the correct View class.
- **View** renders HTML (or JSON for webhook responses).

### History controller model selection

The `History` controller serves four different pages from one class, choosing the
Model at runtime based on which route tokens are present:

```
object_id present → LogDetail
type present      → LogList
source present    → TypeList
(nothing)         → SourceList
```

The `History` Responder mirrors this same logic for View selection. If you add a
new token-driven page, update both.

### Database layer

Every table has a **Data** class (value object) and a **Mapper** class (handles
DB I/O). The Data class holds typed public properties; the Mapper declares
`DATABASE_NAME`, `TABLE`, `PRIMARY_KEY`, `MAPPED_CLASS`, and `MAPPING` constants.

```
Data/Log.php    ← value object, plain public properties
Mapper/Log.php  ← AbstractMapper subclass, constants only
```

### Plugin system

A plugin translates a raw JSON payload into Chronicle's log fields. Plugins
extend `AbstractPlugin` and must implement six methods: `getObjectId`,
`getAction`, `getChangeDate`, `getVersion`, `getChangedBy`, `getData`.

Built-in plugins live in `src/Plugins/` and are auto-discovered by scanning
that directory. Each must declare `public const DESCRIPTION`.

External plugins are registered via `chronicle.plugins` in `config.ini`.

### Authentication & sessions

- Sessions are DB-backed via `SessionHandler`, which stores data in the
  `sessions` table. `session_set_save_handler()` is called in
  `AbstractAuthenticated::filterInput()` and `Auth::filterInput()` — not in
  `index.php` — so webhook requests never touch the session DB.
- Login supports email/password (`PasswordLogin`) and Google OAuth
  (`GoogleOAuthCallback`). OAuth state is stored in `$_SESSION['oauth_state']`
  and validated with `hash_equals()` on callback.
- `GoogleOAuthCallback` checks `chronicle.google.allowed_domains` (comma-separated)
  before creating a session. Empty/absent = all Google accounts allowed.

### CSRF protection

All POST-handling actions extend `AbstractCsrfAction` rather than
`ActionAbstract` directly. `AbstractCsrfAction::doAction()` validates
`$_SESSION['csrf_token']` against the `_csrf_token` POST field using
`hash_equals()`, then delegates to `doCsrfAction()`. Subclasses implement
`doCsrfAction()`, not `doAction()`.

```php
class SaveSource extends AbstractCsrfAction {
    protected function doCsrfAction(array $data = []): mixed {
        // business logic here
    }
}
```

The CSRF token is initialised in `AbstractHTML::generateHeader()` and injected
into forms via `$this->csrfField()`, which returns a hidden input element.

### Admin section routing

The `Admin` controller parses `admin_section` (first path segment after
`/admin`) and `admin_id` (trailing numeric segment) from the request path.
Adding a new admin page requires updating four places in the same commit:

1. `Admin` controller — `getRequestActions()` and `getModels()` match arms
2. `Admin` Responder — `getView()` match arm
3. New `Model/Admin/`, `Action/Admin/`, `View/Admin/` classes

Current sections: `sources`, `types`, `api-keys`, `users`.

---

## Coding Standards

### Braces — 1TBS

Opening brace on the same line as the statement. Always use braces, no exceptions.

```php
// Wrong
public function foo()
{
    if (true)
    {
        bar();
    }
}

// Correct
public function foo() {
    if (true) {
        bar();
    }
}
```

Multi-line conditions: closing parenthesis on its own line.

```php
if (
    $really &&
    $long_condition
) {
    foo();
}
```

### Visibility — protected by default

Use `protected` unless there is a specific reason for `private`. Public API should
be `public`. Never use `private` for properties or methods without a good reason.

### Variables — snake_case

```php
$source_mapper = new SourceMapper();  // correct
$sourceMapper  = new SourceMapper();  // wrong
```

### Single return point

Prefer one `return` at the bottom. Early `return` is acceptable for guard clauses
(validation, null checks at the top of a method). Don't scatter `return` throughout
a long method body.

```php
// Correct — guard clause at top, single exit at bottom
public function getData(): array {
    if (empty($this->source)) {
        return [];
    }

    $result = // ... do work ...

    return $result;
}
```

### No bare functions, no procedural scripts

All logic lives in classes. Helpers go on the class that uses them as `protected`
methods. Don't create standalone utility functions.

### Arrays

Short syntax `[]`, never `array()`. Align `=>` in multi-line arrays. Trailing
comma on the last element of a multi-line array.

```php
$mapping = [
    'type_id'    => [],
    'name'       => [],
    'plugin'     => [],
    'created_at' => ['read_only' => true],
];
```

### use statements

Import every class with a `use` statement — no inline FQCNs in method bodies.
One `use` per line. When two imported names would collide (e.g., both
`Data\Type` and `Mapper\Type`), alias the mapper: `use ... Data\Type as TypeData`.

```php
use DealNews\Chronicle\Data\Type as TypeData;
use DealNews\Chronicle\Mapper\Type as TypeMapper;
```

### PHPDoc

Every class and every method needs a docblock. Parameters and return types in
the docblock even when type hints are present (Doxygen renders both). Use
`@var` on every property.

```php
/**
 * Looks up all sources from the database.
 *
 * @return array<int, Source>
 */
public function getData(): array { ... }
```

---

## Build & Test

```bash
# Install dependencies
composer install

# Run the full test suite
vendor/bin/phpunit

# Run a single test file
vendor/bin/phpunit tests/Plugins/JsonPathTest.php

# Start a local dev server (document root is public/)
php -S localhost:8001 -t public public/index.php

# Run with Docker
docker compose up -d   # requires etc/config.ini to exist
```

There is no separate linting step — style is enforced by code review.

---

## Common Patterns

### Mapper MAPPED_CLASS with aliased imports

In Mapper classes, `Type::class` would resolve to the Mapper itself.
Always alias the Data import:

```php
use DealNews\Chronicle\Data\Type as TypeData;
// ...
public const MAPPED_CLASS = TypeData::class;
```

### Mapper find() usage

```php
$mapper  = new SourceMapper();
$sources = $mapper->find(['name' => $this->source]);

if (empty($sources)) {
    return null;
}

$source = reset($sources);  // find() always returns an array
```

`find()` returns an indexed array of Data objects. Always check for empty before
calling `reset()`.

### Responder key collision avoidance

The History Responder strips route token keys that are superseded by model data
objects, preventing `PropertyMap` from overwriting a `Source` Data object with
a plain string from `$inputs`:

```php
foreach (['source', 'type', 'object_id'] as $key) {
    if (array_key_exists($key, $data)) {
        unset($inputs[$key]);
    }
}
```

If you add a new token-keyed page where the Model returns a Data object under
the same key, add that key to this list.

### Forms with textareas — use form--stacked

The default `form` layout is `display: flex; flex-wrap: wrap` (fields inline).
Any form that contains a `<textarea>` must add `class="form--stacked"` to the
`<form>` element so fields stack vertically and fill the full card width.

When a stacked form has more than one action button (submit + cancel), wrap
them in `<div class="form-actions">` to keep them on the same line.

### Tables must be wrapped in .table-wrap

Every `<table>` must be wrapped in `<div class="table-wrap">`. The wrapper
provides the card styling (border-radius, box-shadow, overflow: hidden) and
handles horizontal scrolling on narrow viewports. The `<table>` element itself
has no card styles.

```html
<div class="table-wrap"><table>
    ...
</table></div>
```

### Plugin constructor injection for testability

`AbstractPlugin` takes `$payload`, `$source`, `$type`. The `JsonPath` plugin
adds an optional `?GetConfig $config = null` fourth parameter, defaulting to the
singleton. This keeps tests free of real config files:

```php
$plugin = new JsonPath($payload, 'source', 'type', $mockConfig);
```

---

## Testing Strategy

Tests live in `tests/` and mirror the `src/` directory structure:
`tests/Service/DifferTest.php` ↔ `src/Service/Differ.php`.

**What to test:**
- Plugin methods against real fixture JSON (no DB needed).
- Service classes (like `Differ`) with plain array/string inputs.
- Exception paths — assert both the exception class and a substring of the message.

**PHPUnit 12 notes:**
- Use `createStub()` (not `createMock()`) when you don't need to assert call
  counts. Stubs avoid "no expectation set" notices in PHPUnit 12.
- Use `willReturnCallback` when the stub needs to return different values based on
  the argument (e.g., a config key map).

**Fixture pattern for config-driven plugins:**

```php
protected function mockConfig(array $map): GetConfig {
    $config = $this->createStub(GetConfig::class);
    $config->method('get')
           ->willReturnCallback(fn(string $key) => $map[$key] ?? null);
    return $config;
}

protected function makePlugin(array $extra = []): JsonPath {
    return new JsonPath(
        $this->fixture,
        'source',
        'type',
        $this->mockConfig(array_merge($this->defaults, $extra))
    );
}
```

**DataProvider for scalar type coverage:**

Use `#[DataProvider('provideX')]` (PHP 8 attribute, not the `@dataProvider` annotation)
and declare provider methods `public static`.

---

## Gotchas & Edge Cases

### Simultaneous create + update events

Some systems fire a `create` and an `update` event at the same millisecond.
`LogDetail::getData()` applies a `usort` after fetching from the DB to force
`create` events to the front, regardless of `change_date`. This ensures the
object history page always diffs from the actual initial state.

Don't remove or reorder this sort — it's load-bearing for the diff view.

### Mapper MAPPED_CLASS must use `::class`, not a string

```php
// Wrong — breaks if class is renamed or moved
public const MAPPED_CLASS = 'DealNews\Chronicle\Data\Type';

// Correct
public const MAPPED_CLASS = TypeData::class;
```

### Plugin keys in config.ini are scoped per source + type

Every JSONPath plugin config key starts with `chronicle.plugin.{source}.{type}.`.
A key set for `dato-item.brands` won't bleed into `dato-item.deals`. This is
intentional — don't add a "global default" layer without discussing it first.

### External plugins are FQCNs; built-in plugins are short names

When the admin UI saves a plugin assignment, built-in plugins store just the
short class name (e.g. `DatoCMS`). External plugins store the full class name
(e.g. `\My\Plugin`). `AbstractPlugin::resolve()` handles both. Don't change how
keys are stored without updating that method.

### Route token key collision in Responders

If a Model returns data under a key that matches a route token name (e.g., `source`,
`type`, `object_id`), `PropertyMap` will try to set a string on a typed property
that expects a Data object. The History Responder strips colliding token keys before
calling `parent::generateView()`. Replicate this pattern in any new Responder that
has the same risk.

### Sessions are DB-backed

`SessionHandler` stores sessions in the `sessions` table (not files). The dev
`config.ini` must point to a working database, otherwise sessions don't persist
and every request redirects to login. SQLite is the easiest option for local dev.

### Source and Type have a description field

Both `Data\Source` and `Data\Type` have a nullable `?string $description`
property mapped to a `TEXT` column. It is optional — null is stored when the
admin leaves the field blank. Display it wherever the name is shown so users
unfamiliar with the data can understand what a source or type represents.

---

## Making Changes

### Adding a new webhook source/type

1. Create the source and type in the admin UI (`/admin/sources`, `/admin/types`).
2. Assign a plugin to the type.
3. For the JSONPath plugin, add config keys to `etc/config.ini`.
4. Test with `curl` (see `README.md` for the example command).

### Adding a new built-in plugin

1. Create `src/Plugins/MyPlugin.php` extending `AbstractPlugin`.
2. Declare `public const DESCRIPTION = 'My Plugin';`.
3. Implement all six abstract methods.
4. Add a fixture file to `tests/fixtures/` and a test class to
   `tests/Plugins/MyPluginTest.php`.
5. The plugin is auto-discovered — no registration needed.

### Adding a new admin page

The `/admin` route is a `starts_with` catch-all in `index.php` — no route
change needed. Add the section slug to the four match arms:

1. `Admin` controller — `getRequestActions()` and `getModels()`
2. `Admin` Responder — `getView()`
3. New `Model/Admin/MyList.php`, `Action/Admin/SaveMy.php`, `View/Admin/MyList.php`

If the form contains a textarea, add `class="form--stacked"` to the `<form>`
and wrap any multi-button row in `<div class="form-actions">`.
Wrap the table in `<div class="table-wrap">`.
Extend `AbstractCsrfAction` and implement `doCsrfAction()`.
Add `_csrf_token` to the controller's POST filters if not already present.

### Adding a new history page / route token

The History controller and Responder both use the same token-presence chain to
select models and views. Update both in the same commit. If the model returns
a Data object under a key that matches a route token, update the key-stripping
list in `History::generateView()`.
