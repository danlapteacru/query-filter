# Query Filter

WordPress plugin that adds **index-backed filtering** for **Query Loop** blocks. Filter state lives in the **Interactivity API** store; results update through a **REST** endpoint that returns fresh `core/query` HTML and filter metadata.

**Requirements:** WordPress **6.7+**, PHP **8.1+**.

## Features

- **Custom index table** (`{prefix}query_filter_index`) storing per-post filter rows for fast ID resolution.
- **Automatic indexing** on publish/update/delete posts and when terms change; optional **cron** batch rebuild; **activation** creates the table and schedules a full rebuild.
- **Gutenberg blocks** (built from `src/` into `build/`):
  - **Filter container** — wraps child filters, links to a Query Loop via `queryId`; **Combine filters** (AND/OR across filters).
  - **Filter: Checkboxes** — multi-select; taxonomy-backed options; **Match** any or all values within that filter.
  - **Filter: Radio** / **Filter: Dropdown** — single choice; same indexed values as checkboxes (taxonomy terms, etc.).
  - **Filter: Number range** — min/max inputs and optional dual range sliders; requires a registered **`Query_Filter_Filter_Range`** (see hooks below).
  - **Filter: Date range** — `after` / `before` (`Y-m-d`); requires **`Query_Filter_Filter_Date_Range`**.
  - **Filter: Search**, **Sort**, **Reset** — live inside the container.
  - **Filter: Pager** — intended inside the Query Loop (pagination).
- **REST API:** `POST /wp-json/query-filter/v1/results` — JSON body parsed by `Query_Filter_Request` (filters, optional `filtersRelationship`, sort, search, page, etc.).
- **Admin:** **Tools → Query Filter** — index stats, schedule full rebuild, clear index.
- **WP-CLI:** `wp query-filter index` — `rebuild`, `post <id>`, `status` (when WP-CLI is available).

Out of the box, the plugin registers a **checkbox filter for each public taxonomy** and indexes published posts.

## Installation

1. Clone or copy this directory into `wp-content/plugins/query-filter` (or symlink).
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
4. Activate **Query Filter** in **Plugins**.

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
3. Inside the container, add **Filter: Checkboxes** (and search/sort/reset as needed). Configure taxonomy/source and filter name.
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
| `filtersRelationship` | `AND` or `OR` across filters |
| `filters` | Discrete: `name: ["slug", …]` or `name: { values, logic }`. Numeric range: `name: { min, max }`. Date range: `name: { after, before }` (ISO `Y-m-d`). |

### Numeric and date facets (PHP)

Taxonomy filters are registered automatically. For **meta price**, **published date**, etc., register filters on the indexer:

```php
add_action(
	'query_filter/indexer/register_filters',
	static function ( Query_Filter_Indexer $indexer ): void {
		$indexer->register_filter(
			new Query_Filter_Filter_Range(
				'price',
				new Query_Filter_Source_Post_Meta( 'price' )
			)
		);
		$indexer->register_filter(
			new Query_Filter_Filter_Date_Range(
				'published',
				new Query_Filter_Source_Post_Date()
			)
		);
	}
);
```

Use the **same filter name** in the block settings (`filterName`) as in `register_filter()`.

## Hooks and customizing the front end

The plugin exposes **WordPress filters** so themes and companion plugins can change **markup** and **REST payloads** without editing plugin files. Design notes: [`docs/superpowers/specs/2026-04-07-query-filter-hooks-design.md`](docs/superpowers/specs/2026-04-07-query-filter-hooks-design.md).

### Registering more blocks (extensions)

Built-in blocks are registered from folders under `build/` listed in **`Query_Filter_Blocks::DEFAULT_BUILD_DIRECTORIES`** (`includes/class-blocks.php`). To add blocks from another plugin without editing core plugin code, use:

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
query-filter.php          # Plugin bootstrap
includes/                 # PHP (classmap autoload)
  class-plugin.php
  class-indexer.php       # Table + indexing
  class-query-engine.php  # Filter → post IDs
  class-rest-controller.php
  class-request.php       # REST JSON → request DTO
  class-renderer.php      # Renders Query Loop for IDs
  class-blocks.php          # Default build/ block dirs; filterable for extensions
  class-render-hooks.php    # apply_filters helpers for block HTML + REST
  filters/                # Filter types
  sources/                # Data sources (taxonomy, meta, …)
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
