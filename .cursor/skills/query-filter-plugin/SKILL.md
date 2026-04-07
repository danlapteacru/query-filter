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
- **Blocks** under `src/` (built to `build/`): `filter-container` (parent), checkboxes, radio, dropdown, number range, date range, search, sort, pager, reset. Interactivity store id: **`query-filter`**.
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
- **Request shape** (REST JSON): `queryId`, `pageId`, `page`, `orderby`, `order`, `search`, optional **`filtersRelationship`** (`AND`|`OR`), **`filters`**: legacy `name → string[]`, discrete `name → { values, logic }`, range `name → { min, max }`, date `name → { after, before }` (ISO dates). Action **`query_filter/indexer/register_filters`** registers `Query_Filter_Filter_Range` / `Date_Range` on the indexer.

## Query engine

- `Query_Filter_Query_Engine::get_post_ids( $active_filters, $between_filters_logic = 'AND' )`.
- Per-filter configs include **`kind`**: `discrete` (`values` + **`logic`**), `range` (numeric bounds), `date_range` (ISO string bounds).
- Between filters: **`AND`** intersects sets; **`OR`** unions (`combine_post_id_sets()`).

## Interactivity / DOM

- Discrete blocks: **`.wp-block-query-filter-filter-checkboxes`**, **`.wp-block-query-filter-filter-radio`**, **`.wp-block-query-filter-filter-dropdown`**. Range: **`.wp-block-query-filter-filter-range`**, **`.wp-block-query-filter-filter-date-range`**. Sort: **`.wp-block-query-filter-filter-sort`**.
- Container URL: discrete = comma list; range/date = `key=min..max` or `after..before`; plus **`search`**, **`pg`**, **`frel`**.
- Server injects initial state via `wp_interactivity_state( 'query-filter', … )` in `render.php` files.
- **Hooks:** `query_filter/blocks/build_directories`, `query_filter/render/block`, `query_filter/render/interactivity_context`, legacy `…/checkboxes/context`, `query_filter/rest/response` — see README. Templates use `$block->name`, not duplicated constants.

## Verification

**Before push**, mirror GitHub Actions (`.github/workflows/ci.yml`):

```bash
composer test && composer phpstan && composer lint:php
npm run lint:js && npm run lint:pkg-json && npm run test:unit && npm run build
```

Quick PHP-only: `composer check` (PHPStan + PHPCS, no PHPUnit).

Integration suite (full WP): `WP_TESTS_DIR=… ./vendor/bin/phpunit -c phpunit-integration.xml.dist` when available.

## Git

- **Use a feature branch** for changes; do not land work only on `local main` without a branch/PR.
- Commit `src/` changes; remind to run **`npm run build`** for runtime `build/` output.
