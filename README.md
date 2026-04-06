# Query Filter

WordPress plugin that adds **index-backed filtering** for **Query Loop** blocks. Filter state lives in the **Interactivity API** store; results update through a **REST** endpoint that returns fresh `core/query` HTML and filter metadata.

**Requirements:** WordPress **6.7+**, PHP **8.1+**.

## Features

- **Custom index table** (`{prefix}query_filter_index`) storing per-post filter rows for fast ID resolution.
- **Automatic indexing** on publish/update/delete posts and when terms change; optional **cron** batch rebuild; **activation** creates the table and schedules a full rebuild.
- **Gutenberg blocks** (built from `src/` into `build/`):
  - **Filter container** — wraps child filters, links to a Query Loop via `queryId`; **Combine filters** (AND/OR across checkbox filters).
  - **Filter: Checkboxes** — taxonomy-backed options; **Match** any or all values within that filter.
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
| `npm run test:unit` | Jest (`tests/js/`) |
| `./vendor/bin/phpunit` | PHPUnit **unit** suite (`tests/phpunit/unit/`) |

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
| `filtersRelationship` | `AND` or `OR` across checkbox filters |
| `filters` | Per filter: legacy `name: ["slug", …]` or `name: { values, logic }` |

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
