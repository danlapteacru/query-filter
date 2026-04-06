# Query Filter — hooks and front-end markup overrides

**Date:** 2026-04-07  
**Status:** Implemented

---

## Goals

1. **Extension points** — `apply_filters` / optional `do_action` at stable boundaries without forking plugin files.
2. **Front markup** — Themes and plugins can change HTML from block `render.php` output while keeping Interactivity attributes (`data-wp-interactive`, `data-wp-context`, `data-wp-on--*`) intact when they do string-level edits.
3. **REST** — Response payload filterable for integrations (e.g. analytics wrappers, extra keys).

---

## Filters

| Hook | Type | Args | Notes |
|------|------|------|--------|
| `query_filter/render/block` | filter | `$html` (string), `$block_name` (string), `$attributes` (array), `$context` (array\|null) | Runs on **final** buffered markup for each block `render.php`. `$context` is the Interactivity context array when applicable; otherwise `null`. |
| `query_filter/render/checkboxes/context` | filter | `$context` (array), `$attributes` (array) | Runs **before** `wp_json_encode` for checkbox blocks. Must return an **array** or the original is kept. |
| `query_filter/rest/response` | filter | `$data` (array) | Keys: `results_html`, `filters`, `total`, `pages`. Return must remain array-shaped for the client. |

---

## Actions (reserved)

Future: `query_filter/render/before`, `query_filter/rest/before_handle` — not required for MVP; filters cover primary use cases.

---

## WordPress core alternative

Themes may use `add_filter( 'render_block', … )` and inspect `$block['blockName']` (e.g. `query-filter/filter-checkboxes`). No plugin hook required; document risk of breaking Interactivity if attributes are removed.

---

## Safety

- Do not remove or corrupt `data-wp-context` JSON for interactive blocks unless the theme also replaces the Interactivity store behavior.
- REST: changing `filters` shape may break `view.js` unless the client is updated.

---

## Implementation

- `Query_Filter_Render_Hooks` in `includes/class-render-hooks.php` — `block_html()`, `checkboxes_context()`.
- Each `src/*/render.php` buffers output and passes through `block_html()`.
- `Query_Filter_Rest_Controller::handle` applies `query_filter/rest/response` before `WP_REST_Response`.
