# Query Filter Plugin — Design Spec

**Date:** 2026-04-01
**Status:** Approved

---

## Overview

A WordPress plugin for advanced, index-powered filtering of Query Loop blocks. Combines the modern block-native architecture of `query-filter-main` with the indexing power and filter depth of FacetWP. Uses the WordPress Interactivity API for all frontend interactions. No shortcodes, no jQuery.

---

## Decisions

| Concern | Decision |
|---|---|
| Integration | Block-only (no shortcodes) |
| Min WP version | 6.7+ (max compatibility) |
| Min PHP version | 8.1+ |
| Filter types (MVP) | Checkboxes, Search, Sort, Pager, Reset |
| Data sources | Taxonomy, Post Meta, ACF, WooCommerce |
| Indexing | Always-on DB index |
| Admin UI | Tools → Query Filter (index stats + rebuild) |
| Terminology | "Filter" not "Facet" throughout |

---

## Architecture

### PHP Class Structure

```
query-filter/
├── query-filter.php               # Plugin header, calls Plugin::instance()
├── includes/
│   ├── class-plugin.php           # Singleton; registers all hooks
│   ├── class-indexer.php          # DB table creation, index/re-index logic
│   ├── class-query-engine.php     # Translates active filters → WP_Query args via index
│   ├── class-renderer.php         # Server-renders post list HTML + filter states
│   ├── class-request.php          # Parses REST request payload
│   ├── class-admin.php            # Tools → Query Filter admin page
│   ├── filters/
│   │   ├── class-filter.php               # Abstract base: index_post(), load_values()
│   │   ├── class-filter-checkboxes.php
│   │   ├── class-filter-search.php
│   │   ├── class-filter-sort.php
│   │   ├── class-filter-pager.php
│   │   └── class-filter-reset.php
│   └── sources/
│       ├── class-source.php               # Abstract base: get_values(post_id): array
│       ├── class-source-taxonomy.php
│       ├── class-source-postmeta.php
│       ├── class-source-acf.php           # Requires ACF active; skips registration if not
│       └── class-source-woocommerce.php   # Requires WooCommerce active; skips if not
├── blocks/
│   ├── filter-container/          # Wraps filter blocks; links to Query Loop via queryId
│   ├── filter-checkboxes/
│   ├── filter-search/
│   ├── filter-sort/
│   ├── filter-pager/              # Lives inside Query Loop (not inside filter-container)
│   └── filter-reset/
└── src/
    ├── store.js                   # Single Interactivity API store for all filter state
    └── api.js                     # Uses @wordpress/api-fetch (no custom HTTP lib)

### JS Dependencies (WordPress packages only)

- `@wordpress/scripts` — build toolchain
- `@wordpress/interactivity` — frontend store + reactive DOM
- `@wordpress/interactivity-router` — client-side navigation
- `@wordpress/api-fetch` — REST requests (handles nonces automatically)
- `@wordpress/i18n` — internationalization
- `@wordpress/blocks` — block registration
- `@wordpress/block-editor` — InspectorControls, block sidebar
- `@wordpress/components` — SelectControl, ToggleControl, TextControl, etc.
- `@wordpress/data` — editor-side state (if needed)
- No custom/third-party JS packages unless no WP package covers the need

### PHP Requirements

- PHP 8.1+ minimum (enums, readonly properties, intersection types allowed)
- Development: TDD — tests written before implementation
```

### Key Design Principles

- `Plugin` singleton is the single place all hooks are registered
- `Filter` abstract base enforces `index_post(int $post_id): array` and `load_values(array $params): array` — each filter type handles its own indexing logic and available-values query
- `Source` adapters decouple data access from filter logic — a Checkboxes filter can pull from a taxonomy or an ACF field without knowing the difference
- Source adapters check for plugin availability at registration time; missing ACF or WooCommerce causes no fatal errors

---

## Database Index Table

```sql
CREATE TABLE wp_query_filter_index (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id        BIGINT UNSIGNED NOT NULL,
  filter_name    VARCHAR(50)     NOT NULL,  -- e.g. "category", "price", "pa_color"
  filter_value   VARCHAR(200)    NOT NULL,  -- raw/sortable value
  display_value  VARCHAR(200)    NOT NULL,  -- human-readable label
  term_id        BIGINT UNSIGNED DEFAULT 0, -- for taxonomy filters
  parent_id      BIGINT UNSIGNED DEFAULT 0, -- for hierarchical terms
  depth          TINYINT         DEFAULT 0,
  PRIMARY KEY (id),
  KEY post_id_idx      (post_id),
  KEY filter_name_idx  (filter_name),
  KEY filter_name_value (filter_name, filter_value)
) ENGINE=InnoDB;
```

### Indexing Lifecycle

- **Plugin activation**: Batch-indexes all published posts (10 at a time via WP Cron)
- **`save_post`**: Re-indexes that single post synchronously (fast)
- **`delete_post`**: Removes all rows for that `post_id`
- **`edited_term` / `delete_term`**: Re-indexes all posts assigned to that term
- Each `Source` adapter provides the values to write per post

---

## Block Structure

### Nesting Rules

- **Filter Container** must be the parent of all filter blocks except Pager
- **Filter Container** references its target Query Loop via `queryId` block context
- **Pager** lives inside the Query Loop block (alongside Post Template)
- Multiple Filter Containers on one page = multiple independent filtered Query Loops supported
- Filter blocks inherit `queryId` from their container via block context

```
Group (page layout)
├── Filter Container  ← links to queryId: 1
│   ├── Filter: Checkboxes  (source: tax/category)
│   ├── Filter: Checkboxes  (source: pa_color)
│   ├── Filter: Search
│   ├── Filter: Sort
│   └── Filter: Reset
└── Query Loop  (queryId: 1)
    ├── Post Template
    │   ├── Post Title
    │   └── Post Featured Image
    └── Filter: Pager  ← lives inside Query Loop
```

---

## Data Flow

1. **User interacts** with a filter block — Interactivity API store updates local state immediately (optimistic UI)
2. **Store dispatches REST request** (debounced):
   ```
   POST /wp-json/query-filter/v1/results
   {
     "queryId": 1,
     "filters": { "category": ["shoes"], "pa_color": ["red"] },
     "page": 1,
     "orderby": "price",
     "search": ""
   }
   ```
3. **Server**: `Request` parses payload → `QueryEngine` runs SQL against `wp_query_filter_index` to get matching `post_id[]` → builds `WP_Query` with `post__in`
4. **Server**: Each `FilterType::load_values()` queries the index for available options + counts. `Renderer` server-renders post list HTML and filter states.
5. **Response** returned to store:
   ```json
   {
     "results_html": "<li>...</li>",
     "filters": { "category": [{ "value": "shoes", "label": "Shoes", "count": 12 }] },
     "total": 47,
     "pages": 3
   }
   ```
6. **Store applies response**: replaces results HTML, updates filter counts, updates URL via `pushState` (shareable/bookmarkable)

---

## REST API

**Endpoint:** `POST /wp-json/query-filter/v1/results`

**Request body:**
```json
{
  "queryId": 1,
  "filters": { "<filter_name>": ["<value>", ...] },
  "page": 1,
  "orderby": "date",
  "order": "DESC",
  "search": ""
}
```

**Response:**
```json
{
  "results_html": "<string>",
  "filters": { "<filter_name>": [{ "value": "", "label": "", "count": 0 }] },
  "total": 0,
  "pages": 0
}
```

---

## Filter Types (MVP)

### Filter: Checkboxes
- Block inspector fields: Label, Show label, Data Source, Selection Logic (AND/OR), Order By, Show at most (limit), Show result counts
- Supports AND logic (must match all selected) and OR logic (match any selected)
- Zero-count terms shown but visually dimmed

### Filter: Search
- Block inspector fields: Label, Show label, Placeholder text
- Debounced input; triggers REST request after typing stops

### Filter: Sort
- Block inspector fields: Label, Sort options (configurable list of orderby/order pairs with labels)
- Renders as a `<select>`

### Filter: Pager
- Block inspector fields: (none beyond standard block controls)
- Renders numbered page links + prev/next
- Lives inside Query Loop block, not Filter Container

### Filter: Reset
- Block inspector fields: Label
- Clears all active filter state and resets to page 1
- Shown as a button; hidden when no filters are active

---

## Data Sources

| Source | Class | Requirement |
|---|---|---|
| Taxonomy | `Source_Taxonomy` | None |
| Post Meta | `Source_Post_Meta` | None |
| ACF | `Source_ACF` | ACF plugin active |
| WooCommerce | `Source_WooCommerce` | WooCommerce active |

Source adapters skip registration silently if their required plugin is inactive.

---

## Admin UI — Tools → Query Filter

Plain PHP, WP admin styles. No Vue.js or React.

**Sections:**
1. **Index Status** — indexed post count, total index rows, last indexed timestamp, status indicator (up to date / needs rebuild)
2. **Actions** — "Rebuild Full Index" button (background via WP Cron, 10 posts/batch), "Clear Index" button (destructive, confirm dialog)
3. **WP-CLI Commands** — reference block showing:
   - `wp query-filter index rebuild`
   - `wp query-filter index post <post_id>`
   - `wp query-filter index status`
   - `wp query-filter index clear`

---

## Frontend UI

- **Layout**: Filters in a sidebar, results in a grid (theme-controlled via block layout)
- **Active filter chips**: Rendered client-side by the Interactivity API store from current filter state — shown in the results header as removable tags (e.g., "Electronics ✕"). Clicking a chip deselects that filter value.
- **Result count**: "Showing X of Y results"
- **Zero-count terms**: Shown but dimmed — not interactive
- **Loading state**: Interactivity API applies a loading class to the results area during fetch; theme can style with CSS (e.g., opacity)
- **No results**: Returns empty `results_html`; theme handles display via standard WP empty query markup

---

## Error Handling

- **REST request fails**: Store catches error, reverts optimistic UI state, shows dismissible inline error notice
- **Index out of sync**: Posts with no index rows won't appear in filtered results. Admin page shows unindexed post count if detected.
- **Missing ACF/WooCommerce**: Source adapters check availability at registration; missing plugins cause no fatal errors

---

## Testing (TDD)

All code is developed test-first: write failing test → implement → pass → refactor.

- **PHPUnit unit tests**: `Indexer::index_post()`, `QueryEngine::get_post_ids()`, each `FilterType::load_values()`, each `Source::get_values()`
- **PHPUnit integration tests**: Seed posts + index → POST to REST endpoint → assert correct post IDs and filter counts
- **Vitest JS tests** (via `@wordpress/scripts`): Store actions, URL state serialization/deserialization
- **No E2E tests in MVP**
- Uses `@wordpress/env` for local WordPress test environment

---

## Out of Scope (MVP)

- Slider, number range, date range, rating, proximity filter types
- Hierarchical term display
- Ghost options (show disabled terms from unfiltered set)
- Multilingual support (WPML/Polylang)
- SearchWP / Relevanssi integration
- Elementor / Beaver Builder integration
- REST API caching layer
