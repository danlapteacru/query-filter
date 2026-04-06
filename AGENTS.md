# Agent instructions — Query Filter

This repository is the **Query Filter** WordPress plugin: block-based, index-backed filtering for **Query Loop** blocks, with **Interactivity API** frontends and a **REST** endpoint that returns rendered HTML plus filter state.

## Quick map

- **PHP**: `includes/` (`class-plugin.php`, `class-rest-controller.php`, `class-request.php`, `class-query-engine.php`, `class-renderer.php`, `class-indexer.php`, `filters/`, `sources/`).
- **Blocks**: `src/<block>/` → **`npm run build`** → `build/` (ignored by git).
- **Tests**: `tests/phpunit/unit/` (default PHPUnit), `tests/phpunit/integration/` (needs WordPress test lib), `tests/js/` (Jest).

## Commands

| Command | Purpose |
|---------|---------|
| `npm run build` | Compile blocks to `build/` |
| `npm run start` | Watch mode |
| `./vendor/bin/phpunit` | Unit tests |
| `npm run test:unit` | Jest |

## Conventions

- PHP: **`declare(strict_types=1);`**, prefer **`[]`** over `array()`, match existing `Query_Filter_*` naming.
- REST body: see `Query_Filter_Request` — structured **`filters`** + optional **`filtersRelationship`**; legacy flat filter arrays still supported.
- Front-end: single store **`query-filter`**; checkbox/sort block selectors use the full **`…-filter-checkboxes`** / **`…-filter-sort`** class names.

## Workflow

- Work on a **feature branch**; open a PR to `main`.
- After editing `src/`, run **`npm run build`** before verifying in WordPress.

## Deeper context

For extended workflows and file-level detail, use the project skill **query-filter-plugin** (`.cursor/skills/query-filter-plugin/SKILL.md`).
