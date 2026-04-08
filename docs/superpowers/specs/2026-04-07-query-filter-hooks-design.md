# Query Filter — hooks and front-end markup overrides

**Date:** 2026-04-07  
**Status:** Implemented (updated: extensible block list + runtime block names)

---

## Goals

1. **Extension points** — `apply_filters` at stable boundaries without forking plugin files.
2. **Front markup** — Themes/plugins change HTML from `render.php` while preserving Interactivity attributes when doing string edits.
3. **REST** — Response payload filterable for integrations.
4. **No duplicated block names** — Render templates use **`$block->name`** (from `block.json`). Built-in blocks are listed once in **`QLIF_Blocks::DEFAULT_BUILD_DIRECTORIES`**; extensions append via **`query_filter/blocks/build_directories`**.

---

## Filters

| Hook | Type | Args | Notes |
|------|------|------|--------|
| `query_filter/blocks/build_directories` | filter | `list<string>` build subdirs | Folders under `build/` that contain `block.json`. |
| `query_filter/render/block` | filter | `$html`, `$block_name`, `$attributes`, `$context` | `$block_name` = `WP_Block::$name`. |
| `query_filter/render/interactivity_context` | filter | `$context`, `$attributes`, `$block_name` | Generic context filter before JSON encode. |
| `query_filter/render/checkboxes/context` | filter | `$context`, `$attributes` | **Legacy**; still run for checkbox block before the generic filter. |
| `query_filter/rest/response` | filter | `$data` (array) | Keys: `results_html`, `filters`, `total`, `pages`. |

## Actions

| Hook | Args | Notes |
|------|------|--------|
| `query_filter/indexer/register_filters` | `QLIF_Indexer $indexer` | Register extra `QLIF_Filter_Checkboxes` filters (e.g. post meta sources). |
| `query_filter/admin/run_rebuild_batches_on_tools_page` | `bool $run` | Default `true`: while a cron rebuild is pending, run batches during **Tools → Query Loop Index Filters** load (for hosts without WP-Cron). |
| `query_filter/admin/rebuild_time_budget_seconds` | `float $seconds` | Default `20` — max wall time per admin request for those batches. |
| `query_filter/admin/rebuild_max_batches_per_request` | `int $max` | Default `500` — safety cap on batches per request. |

---

## WordPress core alternative

`add_filter( 'render_block', … )` using `$block['blockName']`.

---

## Safety

- Preserve `data-wp-context` / directives unless replacing Interactivity behavior.
- REST: changing `filters` shape may break `view.js`.

---

## Implementation

- `QLIF_Blocks` — default directories + `get_build_directories()`.
- `QLIF_Plugin::register_blocks()` uses `QLIF_Blocks::get_build_directories()`.
- `QLIF_Render_Hooks` — `block_html()`, `filter_interactivity_context()`, `filter_checkboxes_interactivity_context()` (legacy + generic).
- Block `render.php` files use `$block->name` for hook arguments.
- `QLIF_Plugin::configure_indexer()` fires **`query_filter/indexer/register_filters`** after taxonomy checkbox filters.
