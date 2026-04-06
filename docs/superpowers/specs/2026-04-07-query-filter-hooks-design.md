# Query Filter — hooks and front-end markup overrides

**Date:** 2026-04-07  
**Status:** Implemented (updated: extensible block list + runtime block names)

---

## Goals

1. **Extension points** — `apply_filters` at stable boundaries without forking plugin files.
2. **Front markup** — Themes/plugins change HTML from `render.php` while preserving Interactivity attributes when doing string edits.
3. **REST** — Response payload filterable for integrations.
4. **No duplicated block names** — Render templates use **`$block->name`** (from `block.json`). Built-in blocks are listed once in **`Query_Filter_Blocks::DEFAULT_BUILD_DIRECTORIES`**; extensions append via **`query_filter/blocks/build_directories`**.

---

## Filters

| Hook | Type | Args | Notes |
|------|------|------|--------|
| `query_filter/blocks/build_directories` | filter | `list<string>` build subdirs | Folders under `build/` that contain `block.json`. |
| `query_filter/render/block` | filter | `$html`, `$block_name`, `$attributes`, `$context` | `$block_name` = `WP_Block::$name`. |
| `query_filter/render/interactivity_context` | filter | `$context`, `$attributes`, `$block_name` | Generic context filter before JSON encode. |
| `query_filter/render/checkboxes/context` | filter | `$context`, `$attributes` | **Legacy**; still run for checkbox block before the generic filter. |
| `query_filter/rest/response` | filter | `$data` (array) | Keys: `results_html`, `filters`, `total`, `pages`. |

---

## WordPress core alternative

`add_filter( 'render_block', … )` using `$block['blockName']`.

---

## Safety

- Preserve `data-wp-context` / directives unless replacing Interactivity behavior.
- REST: changing `filters` shape may break `view.js`.

---

## Implementation

- `Query_Filter_Blocks` — default directories + `get_build_directories()`.
- `Query_Filter_Plugin::register_blocks()` uses `Query_Filter_Blocks::get_build_directories()`.
- `Query_Filter_Render_Hooks` — `block_html()`, `filter_interactivity_context()`, `filter_checkboxes_interactivity_context()` (legacy + generic).
- Block `render.php` files use `$block->name` for hook arguments.
