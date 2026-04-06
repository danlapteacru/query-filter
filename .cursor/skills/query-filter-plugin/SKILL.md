---
name: query-filter-plugin
description: >-
  Develops the Query Filter WordPress plugin (index-powered Query Loop filters,
  REST renderer, Gutenberg blocks, Interactivity API). Use when editing this
  repository, adding filter blocks, changing REST or the query engine, or when
  the user mentions query-filter, Query Loop filtering, or query-filter/v1.
---

# Query Filter plugin

## What this repo is

- **WordPress plugin** (PHP 8.1+, WP 6.7+): custom DB index → post IDs → server-rendered Query Loop HTML.
- **Blocks** under `src/` (built to `build/`): `filter-container` (parent), checkboxes, search, sort, pager, reset. Interactivity store id: **`query-filter`**.
- **REST**: `POST /wp-json/query-filter/v1/results` — body parsed by `Query_Filter_Request::from_array()`.

## Layout

| Area | Path |
|------|------|
| Bootstrap | `query-filter.php` |
| Core PHP | `includes/*.php`, `includes/filters/`, `includes/sources/` |
| Block source | `src/<block-name>/` (`block.json`, `index.js`, `edit.js`, `render.php`, optional `view.js`) |
| Built assets | `build/` (gitignored; run build after changing `src/`) |
| Unit PHPUnit | `tests/phpunit/unit/` (default `phpunit.xml.dist`) |
| Integration PHPUnit | `tests/phpunit/integration/` (`phpunit-integration.xml.dist`, needs `WP_TESTS_DIR`) |
| Jest | `tests/js/` |
| Specs / plans | `docs/superpowers/specs/`, `docs/superpowers/plans/` |

## PHP conventions

- `declare(strict_types=1);` on new files.
- Prefer **`[]`** over `array()` for literals (match surrounding file).
- Classes: `Query_Filter_*`, typically `final`, classmap autoload via `composer.json`.
- **Request shape** (REST JSON): `queryId`, `pageId`, `page`, `orderby`, `order`, `search`, optional **`filtersRelationship`** (`AND`|`OR`), **`filters`** as either legacy `name → string[]` or `name → { values, logic }`.

## Query engine

- `Query_Filter_Query_Engine::get_post_ids( $active_filters, $between_filters_logic = 'AND' )`.
- Per-filter: `values` + **`logic`** (`OR` = any value, `AND` = all values for that filter).
- Between filters: **`AND`** intersects sets; **`OR`** unions (`combine_post_id_sets()`).

## Interactivity / DOM

- Checkbox wrapper class from block name `query-filter/filter-checkboxes`: **`.wp-block-query-filter-filter-checkboxes`** (not `…-checkboxes`).
- Sort block: **`.wp-block-query-filter-filter-sort`**.
- Container pushes URL state: filter param names = filter keys; **`search`**, **`pg`**, **`frel`** (between-filter OR); avoid legacy `qf_` prefix.
- Server injects initial state via `wp_interactivity_state( 'query-filter', … )` in `render.php` files.

## Verification

```bash
npm run build
./vendor/bin/phpunit
npm run test:unit
```

Integration suite (full WP): `WP_TESTS_DIR=… ./vendor/bin/phpunit -c phpunit-integration.xml.dist` when available.

## Git

- **Use a feature branch** for changes; do not land work only on `local main` without a branch/PR.
- Commit `src/` changes; remind to run **`npm run build`** for runtime `build/` output.
