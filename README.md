# Query Loop Index Filters

**Index-backed filters for the core Query Loop.**

WordPress plugin that adds **index-backed filtering** for **Query Loop** blocks. Filter state lives in the **Interactivity API** store; results update through a **REST** endpoint that returns fresh `core/query` HTML and filter metadata.

**Scope (current release):** Discrete facets only (checkbox, radio, dropdown); no range or date facets (yet).

**Requirements:** WordPress **6.7+**, PHP **8.1+**.

## Features

- **Custom index table** (`{prefix}query_filter_index`) storing per-post filter rows for fast ID resolution.
- **Automatic indexing** on publish/update/delete posts and when terms change; optional **cron** batch rebuild; **activation** creates the table and schedules a full rebuild.
- **Gutenberg blocks** (built from `src/` into `build/`):
  - **Filter container** — wraps child filters, links to a Query Loop via `queryId`; **Combine filters** (AND/OR across filters).
  - **Filter: Checkboxes** — multi-select; taxonomy-backed options; **Match** any or all values within that filter.
  - **Filter: Radio** / **Filter: Dropdown** — single choice; same indexed values as checkboxes (taxonomy terms, etc.).
  - **Filter: Search**, **Sort**, **Reset** — live inside the container. Search can use **WordPress** default `s` matching or **SearchWP** (`\SWP_Query` with `post__in` scoped to facet results, similar to FacetWP + SearchWP).
  - **Filter: Pager** — intended inside the Query Loop (pagination).
- **REST API:** `POST /wp-json/query-filter/v1/results` — JSON body parsed by `QLIF_Request` (filters, optional `filtersRelationship`, sort, search, page, etc.).
- **Admin:** **Tools → Query Loop Index Filters** — index stats, schedule full rebuild, clear index. If **Rebuilding…** never finishes (common on localhost when WP-Cron does not run), **refresh that page**; the plugin runs rebuild batches during the request. Filters: `query_filter/admin/run_rebuild_batches_on_tools_page`, `query_filter/admin/rebuild_time_budget_seconds` (default 20), `query_filter/admin/rebuild_max_batches_per_request` (default 500).
- **WP-CLI:** `wp query-filter index` — `rebuild`, `post <id>`, `status` (when WP-CLI is available).

Out of the box, the plugin registers a **checkbox filter for each public taxonomy** and indexes published posts.

## Getting started

1. Add or select a **Query Loop** on the page and note its **Query Loop ID** (block sidebar).
2. Insert a **Filter Container** block and set **Query Loop ID** to match that loop.
3. Inside the container, add **one** taxonomy-backed facet (e.g. **Filter: Checkboxes**) and align **Filter name** with the taxonomy slug (see block help / Tools screen if counts are empty).

## Installation

1. Clone or copy this directory into `wp-content/plugins/query-loop-index-filters` (WordPress.org-style folder name) or `wp-content/plugins/query-filter` for local development (or symlink).
2. Install PHP dependencies:
   ```bash
   composer install --no-dev
   ```
   For development (PHPUnit):
   ```bash
   composer install
   ```
3. Install and build JavaScript:
   ```bash
   npm install
   npm run build
   ```
4. Activate **Query Loop Index Filters** in **Plugins**.

The `build/` directory is produced by the build step and is not required in git for production if you ship prebuilt assets.

## Development

| Command | Description |
|---------|-------------|
| `npm run build` | Production build of blocks → `build/` |
| `npm run start` | Watch mode |
| `npm run lint` | ESLint (`src/`, `tests/js/`) + `package.json` lint |
| `npm run lint:js:fix` | ESLint with `--fix` |
| `npm run test:unit` | Jest (`tests/js/`) |
| `composer test` | PHPUnit **unit** suite (`tests/phpunit/unit/`) |
| `composer phpstan` | PHPStan (`phpstan.neon.dist`, level 6) |
| `composer lint:php` | PHPCS (WordPress-Core–based ruleset, `phpcs.xml.dist`) |
| `composer lint:php:fix` | PHPCBF auto-fix |
| `composer check` | PHPStan + PHPCS |

**CI:** GitHub Actions (`.github/workflows/ci.yml`) runs the PHP and JavaScript jobs above on push and pull requests.

**wp-env** (optional): `.wp-env.json` loads this folder as a plugin. Example:

```bash
npx wp-env start
```

**Integration tests** (`tests/phpunit/integration/`) need a full WordPress test install:

```bash
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
./vendor/bin/phpunit -c phpunit-integration.xml.dist
```

## Using the blocks

1. Add a **Query Loop** and note its **Query ID** (block settings).
2. Add **Filter Container**, set **Query Loop ID** to match.
3. Inside the container, add **Filter: Checkboxes** / **Radio** / **Dropdown** (and search/sort/reset as needed). **Filter name** must match the key in the index: for built-in taxonomy filters that is the **taxonomy slug** (e.g. `category`), not a display label. If you rename “Filter name” in the sidebar, keep it equal to **Source key** for taxonomy, or options stay empty. Radio/dropdown render templates fall back to Source key when the name does not match the index.
4. Set **Combine filters** on the container to **Match all (AND)** or **Match any (OR)** across multiple checkbox filters.
5. On each checkbox block, set **Match** for multiple selected terms: **any** vs **all**.

Shareable URL parameters use short keys: filter names as query keys (comma-separated values), `search`, `pg` (page), and `frel=or` when between-filter mode is OR.

## REST request (summary)

`POST /wp-json/query-filter/v1/results`

Typical JSON fields:

| Field | Role |
|-------|------|
| `queryId`, `pageId` | Target Query Loop and page context |
| `page`, `orderby`, `order`, `search` | Pagination and query refinement |
| `searchSource`, `searchwpEngine` | Optional. `searchSource`: `wordpress` (default) or `searchwp` when [SearchWP](https://searchwp.com/) is active. `searchwpEngine`: engine name (default `default`). Usually set from **Filter: Search** block attributes, not hand-written. |
| `filtersRelationship` | `AND` or `OR` across filters |
| `filters` | Discrete only: `name: ["slug", …]` or `name: { values, logic }`. |

### Extra discrete filters (PHP)

Taxonomy filters are registered automatically. Register additional **`QLIF_Filter_Checkboxes`** filters (e.g. post meta) on the indexer:

```php
add_action(
	'query_filter/indexer/register_filters',
	static function ( QLIF_Indexer $indexer ): void {
		$indexer->register_filter(
			new QLIF_Filter_Checkboxes(
				'brand',
				new QLIF_Source_Post_Meta( 'brand' )
			)
		);
	}
);
```

Use the **same filter name** in the block (`filterName`) as in `register_filter()`, then rebuild the index if needed.

## Hooks and customizing the front end

The plugin exposes **WordPress filters** so themes and companion plugins can change **markup** and **REST payloads** without editing plugin files. Design notes: [`docs/superpowers/specs/2026-04-07-query-filter-hooks-design.md`](docs/superpowers/specs/2026-04-07-query-filter-hooks-design.md).

### Registering more blocks (extensions)

Built-in blocks are registered from folders under `build/` listed in **`QLIF_Blocks::DEFAULT_BUILD_DIRECTORIES`** (`includes/qlif-blocks.php`). To add blocks from another plugin without editing core plugin code, use:

```php
add_filter( 'query_filter/blocks/build_directories', function ( array $dirs ) {
	$dirs[] = 'my-addon-filter'; // build/my-addon-filter/block.json
	return $dirs;
} );
```

The **block name** passed to render hooks is always the `name` from that block’s `block.json` (available in templates as `$block->name`), so you do not need to duplicate strings across PHP files.

### Supported filters

| Hook | When it runs | Arguments |
|------|----------------|-----------|
| **`query_filter/blocks/build_directories`** | During `init` block registration | `list<string>` folder names under `build/` |
| **`query_filter/render/block`** | After each block’s `render.php` template has printed | `$html`, `$block_name` (from `WP_Block`), `$attributes`, `$context` (array\|null) |
| **`query_filter/render/interactivity_context`** | When a template calls the helper before `data-wp-context` JSON | `$context`, `$attributes`, `$block_name` — **return an array** |
| **`query_filter/render/checkboxes/context`** | *(Legacy)* Same as above but only chained for the checkbox block | `$context`, `$attributes` — prefer **`query_filter/render/interactivity_context`** and inspect `$block_name` |
| **`query_filter/rest/response`** | Before the REST response for `POST …/query-filter/v1/results` | `$data`: `results_html`, `filters`, `total`, `pages` — **must return an array** |

### Example: wrap one block’s markup (PHP)

```php
add_filter( 'query_filter/render/block', function ( $html, $block_name, $attributes, $context ) {
	if ( ! str_ends_with( $block_name, '/filter-checkboxes' ) ) {
		return $html;
	}
	return '<div class="my-theme-filter-card">' . $html . '</div>';
}, 10, 4 );
```

Use your block’s real `block.json` `name`, or match on a suffix/prefix your namespace uses.

Keep **`data-wp-interactive`**, **`data-wp-context`**, and **`data-wp-on--*`** intact unless you replace the Interactivity behavior as well.

### Example: interactivity context (PHP)

Use only to adjust **presentation-related** context. Changing `filterName` or `logic` on checkbox blocks can desync the client store.

```php
add_filter( 'query_filter/render/interactivity_context', function ( $context, $attributes, $block_name ) {
	if ( ! str_ends_with( $block_name, '/filter-checkboxes' ) ) {
		return $context;
	}
	return $context; // return modified array
}, 10, 3 );
```

### Without plugin hooks (core WordPress)

You can use **`render_block`** and compare `$block['blockName']` to your block’s registered name. Same warning: do not strip Interactivity attributes unless you know the impact.

### CSS-only (no PHP)

Use block classes (e.g. `.wp-block-query-filter-filter-checkboxes`) and **theme.json** / **Additional CSS** for layout and branding.

## Project layout

```
query-loop-index-filters.php  # Plugin bootstrap
includes/                     # PHP (classmap autoload; `qlif-*.php` = QLIF_* classes)
  qlif-plugin.php
  qlif-indexer.php            # Table + indexing
  qlif-query-engine.php         # Filter → post IDs
  qlif-rest-controller.php
  qlif-request.php             # REST JSON → request DTO
  qlif-renderer.php           # Renders Query Loop for IDs
  qlif-blocks.php             # Default build/ block dirs; filterable for extensions
  qlif-render-hooks.php       # apply_filters helpers for block HTML + REST
  filters/                    # qlif-filter-*.php
  sources/                    # qlif-source-*.php
src/                      # Block source (JS + render.php)
build/                    # Compiled blocks (gitignored)
tests/phpunit/unit/       # Default PHPUnit
tests/phpunit/integration/# Full WP tests
docs/superpowers/         # Design specs and plans
```

## License

GPL-2.0-or-later (see plugin header).

## Contributing / AI context

- **`AGENTS.md`** — short agent-oriented overview.
- **`.cursor/rules/`** and **`.cursor/skills/query-filter-plugin/`** — Cursor-specific guidance aligned with this codebase.
