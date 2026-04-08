# Agent instructions — Query Loop Index Filters (QLIF)

This repository is the **Query Loop Index Filters** WordPress plugin: block-based, index-backed filtering for **Query Loop** blocks, with **Interactivity API** frontends and a **REST** endpoint that returns rendered HTML plus filter state.

## Quick map

- **PHP**: `includes/` (`qlif-plugin.php`, `qlif-rest-controller.php`, `qlif-request.php`, `qlif-query-engine.php`, `qlif-renderer.php`, `qlif-indexer.php`, `filters/`, `sources/`). Core classes use the **`QLIF_*`** prefix.
- **Blocks**: `src/<block>/` → **`npm run build`** → `build/` (ignored by git). Registration uses **`QLIF_Blocks::get_build_directories()`** (filter **`query_filter/blocks/build_directories`**). Facet blocks: checkboxes, radio, dropdown (discrete). Extra facets via **`query_filter/indexer/register_filters`**. Render hooks use **`$block->name`**; see README.
- **Tests**: `tests/phpunit/unit/` (default PHPUnit), `tests/phpunit/integration/` (needs WordPress test lib), `tests/js/` (Jest).

## Commands

| Command | Purpose |
|---------|---------|
| `npm run build` | Compile blocks to `build/` |
| `npm run start` | Watch mode |
| `npm run lint` | ESLint + package.json lint |
| `npm run test:unit` | Jest |
| `composer test` | PHPUnit (unit) |
| `composer check` | PHPStan + PHPCS |

## Conventions

- PHP: **`declare(strict_types=1);`**, prefer **`[]`** over `array()`, match existing **`QLIF_*`** class naming.
- REST body: see **`QLIF_Request`** — **`filters`** are discrete (`values` + `logic`); optional **`filtersRelationship`**; legacy flat arrays still supported.
- Front-end: single store **`query-filter`**; discrete facets share sync via **`.wp-block-query-filter-filter-checkboxes`**, **`-radio`**, **`-dropdown`**; sort **`-filter-sort`**.

## Workflow

- Work on a **feature branch**; open a PR to `main`.
- **Before push**, mirror CI (`.github/workflows/ci.yml`): `composer test`, `composer phpstan`, `composer lint:php`, `npm run lint:js`, `npm run lint:pkg-json`, `npm run test:unit`, `npm run build`.
- After editing `src/`, run **`npm run build`** before verifying in WordPress.

## Deeper context

For extended workflows and file-level detail, use the project skill **query-filter-plugin** (`.cursor/skills/query-filter-plugin/SKILL.md`).
