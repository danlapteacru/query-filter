# Query Filter WordPress Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use @superpowers/subagent-driven-development (recommended) or @superpowers/executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a block-only WordPress plugin that indexes post/filter data in a custom table, exposes `POST /wp-json/query-filter/v1/results`, and drives Query Loop results via the Interactivity API—matching the approved design spec.

**Architecture:** PHP core owns indexing (`Indexer`), filter/source adapters, SQL resolution (`QueryEngine`), REST parsing (`Request`), and HTML rendering (`Renderer`). Block editor registers a filter container (with `queryId` targeting a Query Loop) and child filter blocks; pager lives inside the Query Loop. Frontend uses one Interactivity store plus `@wordpress/api-fetch`. Admin is plain PHP under Tools → Query Filter; WP-CLI wraps index operations.

**Tech stack:** WordPress 6.7+, PHP 8.1+, `@wordpress/scripts` + Interactivity API, PHPUnit (wp-phpunit / `yoast/wp-test-utils` or core bootstrap), Vitest via wp-scripts, `@wordpress/env` for local WP.

**Spec reference:** `docs/superpowers/specs/2026-04-01-query-filter-design.md`

**Reference code (optional):** `old/query-filter-main/` for block patterns only—do not copy FacetWP-style architecture; follow this spec.

---

## File map (create unless noted)

All paths are relative to repository root `query-filter/` (plugin root).

| Path | Responsibility |
|------|----------------|
| `query-filter.php` | Plugin header, constants, autoload/bootstrap, `Plugin::instance()` |
| `composer.json` | PHP autoload (classmap or PSR-4 for `includes/`), PHPUnit dev deps |
| `package.json` | `@wordpress/scripts`, `@wordpress/env`, block build |
| `phpunit.xml.dist` | PHPUnit config pointing at WP test install |
| `tests/phpunit/bootstrap.php` | Loads WordPress test environment |
| `includes/class-plugin.php` | Singleton: hooks registration only |
| `includes/class-indexer.php` | Table DDL, `index_post`, `delete_for_post`, cron batch, term hooks |
| `includes/class-query-engine.php` | Active filters → `post__in` via index SQL; coordinates `Filter` types |
| `includes/class-renderer.php` | Runs `WP_Query`, captures post list markup, assembles REST response shape |
| `includes/class-request.php` | Validates/parses REST JSON → DTO/array for engine |
| `includes/class-admin.php` | Tools → Query Filter screen |
| `includes/class-rest-controller.php` | Registers `query-filter/v1/results` |
| `includes/class-cli.php` | WP-CLI `query-filter index *` commands |
| `includes/sources/class-source.php` | Abstract `get_values(int $post_id): array` |
| `includes/sources/class-source-taxonomy.php` | Taxonomy terms per post |
| `includes/sources/class-source-postmeta.php` | Post meta values |
| `includes/sources/class-source-acf.php` | ACF values if ACF active |
| `includes/sources/class-source-woocommerce.php` | Woo attributes if Woo active |
| `includes/filters/class-filter.php` | Abstract `index_post`, `load_values` contract |
| `includes/filters/class-filter-checkboxes.php` | Checkbox facet indexing + counts |
| `includes/filters/class-filter-search.php` | Search param (may not index rows; spec: REST payload) |
| `includes/filters/class-filter-sort.php` | orderby/order |
| `includes/filters/class-filter-pager.php` | page math |
| `includes/filters/class-filter-reset.php` | client-only; server no-op or omitted |
| `blocks/filter-container/` | `block.json`, `edit.js`, `render.php`, styles |
| `blocks/filter-checkboxes/` | … |
| `blocks/filter-search/` | … |
| `blocks/filter-sort/` | … |
| `blocks/filter-pager/` | … (parent: `core/query`) |
| `blocks/filter-reset/` | … |
| `src/store.js` | Interactivity store: state, debounced fetch, optimistic updates |
| `src/api.js` | Thin wrapper around `api-fetch` for results endpoint |
| `tests/phpunit/unit/` | Fast tests with mocks where possible |
| `tests/phpunit/integration/` | REST + DB + indexing |

Adjust class prefixes to your chosen convention (`Query_Filter_*` or namespace); stay consistent.

---

## Prerequisite (human / agent)

- [ ] **Use @superpowers/using-git-worktrees** (or explicit consent) before large implementation on `main`.

---

### Task 1: Scaffold tooling and empty plugin

**Files:**

- Create: `query-filter/query-filter.php`
- Create: `query-filter/composer.json`
- Create: `query-filter/package.json`
- Create: `query-filter/phpunit.xml.dist`
- Create: `query-filter/tests/phpunit/bootstrap.php`
- Create: `query-filter/.wp-env.json` (or align with `@wordpress/env` defaults)

- [ ] **Step 1: Write failing PHPUnit test** that asserts the plugin’s main constant or `class_exists` for `Query_Filter_Plugin` (or your class name) after loading `query-filter.php` in bootstrap.

```php
// tests/phpunit/unit/PluginExistsTest.php
public function test_plugin_class_exists(): void {
    $this->assertTrue( class_exists( \Query_Filter_Plugin::class ) );
}
```

- [ ] **Step 2: Run test — expect FAIL**  
  Run: `composer install && vendor/bin/phpunit tests/phpunit/unit/PluginExistsTest.php`  
  Expected: class not found or bootstrap error.

- [ ] **Step 3: Minimal implementation** — `query-filter.php` loads Composer autoload, defines `QUERY_FILTER_VERSION`, requires `includes/class-plugin.php`, calls `Query_Filter_Plugin::instance()`. `class-plugin.php` is an empty singleton with `private function __construct() {}`.

- [ ] **Step 4: Run test — expect PASS**

- [ ] **Step 5: Commit**  
  `git add query-filter/ composer.lock` (if any) && `git commit -m "chore: scaffold query-filter plugin and PHPUnit"`

---

### Task 2: Database table creation on activation

**Files:**

- Create: `includes/class-indexer.php` (table name helper + `create_table`)
- Modify: `includes/class-plugin.php` — register `register_activation_hook` → `Indexer::create_table`

- [ ] **Step 1: Integration test** — after `activate_plugin` or calling activation callback in test env, assert `$wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}query_filter_index'" )` is non-empty.

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** DDL per spec (columns: `post_id`, `filter_name`, `filter_value`, `display_value`, `term_id`, `parent_id`, `depth`, keys). Use `dbDelta` or `$wpdb->query` with `CREATE TABLE {$wpdb->prefix}query_filter_index`.

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(indexer): create index table on activation"`

---

### Task 3: Source abstraction + taxonomy source (TDD)

**Files:**

- Create: `includes/sources/class-source.php`
- Create: `includes/sources/class-source-taxonomy.php`
- Test: `tests/phpunit/unit/SourceTaxonomyTest.php`

- [ ] **Step 1: Test** — Create a post, assign term to `category`, instantiate source configured for `category`, assert `get_values( $post_id )` returns expected shape (value + label + optional term metadata for index rows).

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** abstract + taxonomy adapter only (no ACF/Woo yet).

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(sources): add taxonomy source adapter"`

---

### Task 4: Post meta + conditional ACF + Woo sources

**Files:**

- Create: `includes/sources/class-source-postmeta.php`
- Create: `includes/sources/class-source-acf.php` (guard every entry point with `class_exists` / function checks)
- Create: `includes/sources/class-source-woocommerce.php` (same)

- [ ] **Step 1: Unit tests** for post meta source (array meta, scalar meta). Skip ACF/Woo tests when extensions not loaded (`markTestSkipped`).

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** adapters; registration in `Plugin` only adds classes when dependencies exist.

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(sources): post meta, ACF, WooCommerce adapters"`

---

### Task 5: Filter abstraction + checkboxes filter indexing

**Files:**

- Create: `includes/filters/class-filter.php`
- Create: `includes/filters/class-filter-checkboxes.php`
- Modify: `includes/class-indexer.php` — `index_post( $post_id )` delegates to registered filters/sources

- [ ] **Step 1: Test** — Given a checkboxes filter config (filter name + taxonomy source), `index_post` writes expected rows to index table.

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** `Filter::index_post` contract returning row arrays; `Indexer` deletes existing rows for post + filter names then inserts.

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(filters): abstract filter and checkbox indexing"`

---

### Task 6: Indexer lifecycle hooks

**Files:**

- Modify: `includes/class-plugin.php`
- Modify: `includes/class-indexer.php`

- [ ] **Step 1: Integration tests** — `save_post` updates index; `delete_post` removes rows; `edited_term` reindexes affected posts (can limit to one term taxonomy for test speed).

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** hooks + cron batch (10 posts) on activation for full rebuild scheduling.

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(indexer): save_post, delete_post, term hooks, cron batch"`

---

### Task 7: QueryEngine — matching post IDs from index

**Files:**

- Create: `includes/class-query-engine.php`
- Test: `tests/phpunit/unit/QueryEngineTest.php`

- [ ] **Step 1: Test** — Seed index rows for two posts and two filter values; assert AND vs OR checkbox logic returns correct `post_id` lists per spec.

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** SQL using `$wpdb->prepare` with intersection/union patterns; empty filter set should mean “all indexed posts” or defer to base Query Loop query—**document choice in code comment** to match how `Renderer` combines with `WP_Query`.

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(engine): resolve post IDs from index for checkbox filters"`

---

### Task 8: Request parser + REST route skeleton

**Files:**

- Create: `includes/class-request.php`
- Create: `includes/class-rest-controller.php`
- Modify: `includes/class-plugin.php` — `rest_api_init`

- [ ] **Step 1: Integration test** — `POST /wp-json/query-filter/v1/results` with invalid JSON returns 4xx; valid minimal body returns 200 + JSON keys `results_html`, `filters`, `total`, `pages` (empty strings/zero allowed).

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** `Request::from_rest_request( WP_REST_Request $request )` with `sanitize_text_field` / array sanitization for filters.

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(rest): results endpoint skeleton and request parsing"`

---

### Task 9: Renderer + Filter::load_values (checkbox counts)

**Files:**

- Create: `includes/class-renderer.php`
- Modify: `includes/filters/class-filter-checkboxes.php`
- Modify: `includes/class-rest-controller.php`

- [ ] **Step 1: Test** — End-to-end: indexed posts + REST call returns non-empty `filters[filter_name][*].count` matching query constraints.

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** `Renderer` builds `WP_Query` with `post__in` from `QueryEngine`, captures loop HTML (reuse Query Loop markup strategy from spec: server-rendered list items / inner blocks as feasible in MVP). Implement `load_values` SQL against index for counts.

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(renderer): WP_Query HTML + checkbox load_values counts"`

---

### Task 10: Search, sort, pager server behavior

**Files:**

- Create/modify: `includes/filters/class-filter-search.php`, `class-filter-sort.php`, `class-filter-pager.php`
- Modify: `includes/class-query-engine.php`, `includes/class-renderer.php`

- [ ] **Step 1: Tests** — REST with `search`, `orderby`/`order`, `page` changes `WP_Query` args and `pages`/`total` correctly.

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement** (YAGNI: minimal behavior that satisfies spec; pager math from `posts_per_page`).

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(filters): search, sort, pager server-side"`

---

### Task 11: WP-CLI commands

**Files:**

- Create: `includes/class-cli.php`
- Modify: `includes/class-plugin.php` — `if ( defined( 'WP_CLI' ) && WP_CLI ) { … }`

- [ ] **Step 1: Manual or CLI test script** — document in step: run `wp query-filter index status` in `@wordpress/env` and expect exit 0 + human-readable output.

- [ ] **Step 2: Implement** `rebuild`, `post <id>`, `status`, `clear` delegating to `Indexer`.

- [ ] **Step 3: Commit**  
  `git commit -m "feat(cli): query-filter index commands"`

---

### Task 12: Admin page Tools → Query Filter

**Files:**

- Create: `includes/class-admin.php`
- Modify: `includes/class-plugin.php` — `admin_menu`

- [ ] **Step 1: Manual test checklist** (no E2E in MVP): screen renders; rebuild schedules cron; clear shows confirm in browser.

- [ ] **Step 2: Implement** sections per spec (status, actions, WP-CLI reference).

- [ ] **Step 3: Commit**  
  `git commit -m "feat(admin): Tools screen for index status and actions"`

---

### Task 13: Block build pipeline + filter-container block

**Files:**

- Create: `blocks/filter-container/block.json` (uses `supports.__experimentalLayout` as needed, provides block context for `queryId`)
- Create: `blocks/filter-container/edit.js`, `index.js`, `render.php`, `style.scss`
- Modify: `package.json` scripts — build all blocks

- [ ] **Step 1: Build** — `npm install && npm run build` succeeds.

- [ ] **Step 2: Manual** — insert Filter Container in editor, pick target Query Loop (store `queryId` attribute per `query-filter-main` patterns in `old/query-filter-main`).

- [ ] **Step 3: Commit**  
  `git commit -m "feat(blocks): filter container and build pipeline"`

---

### Task 14: Child filter blocks (editor + server markup)

**Files:**

- Create under `blocks/filter-checkboxes/`, `filter-search/`, `filter-sort/`, `filter-pager/`, `filter-reset/`

- [ ] **Step 1: For each block** — `block.json` with correct `parent` (`filter-container` vs `core/query` for pager), inspector controls per spec.

- [ ] **Step 2: `render.php`** outputs minimal HTML with Interactivity directives / `data-wp-interactive` wiring consistent with `src/store.js`.

- [ ] **Step 3: Build + manual smoke** in editor.

- [ ] **Step 4: Commit**  
  `git commit -m "feat(blocks): MVP filter blocks"`

---

### Task 15: Interactivity store + API client + URL state

**Files:**

- Create: `src/store.js`, `src/api.js`
- Modify: block `view.js` files or `render.php` script modules as per WP 6.7 Interactivity patterns

- [ ] **Step 1: Vitest** — pure functions for serializing/deserializing filter state to URL search params; mock `api-fetch`.

```js
// tests/js/state-url.test.js
expect( serializeState( { filters: { category: [ 'shoes' ] }, page: 1 } ) ).toContain( 'category' );
```

- [ ] **Step 2: Run** — `npm run test:unit` (or configured script) **FAIL**

- [ ] **Step 3: Implement** debounced POST, optimistic UI rollback on error, `pushState` sync.

- [ ] **Step 4: Run — PASS**

- [ ] **Step 5: Commit**  
  `git commit -m "feat(frontend): interactivity store, api client, URL state"`

---

### Task 16: Frontend UX from spec

**Files:**

- Modify: `src/store.js`, block styles, optional small PHP hooks for results header wrapper

- [ ] **Step 1: Implement** active filter chips, “Showing X of Y”, loading class on results region, zero-count dimming (CSS + store flags), inline error notice on fetch failure.

- [ ] **Step 2: Manual verification** in browser.

- [ ] **Step 3: Commit**  
  `git commit -m "feat(frontend): chips, counts, loading, errors"`

---

### Task 17: Final verification and hardening

- [ ] Run full PHPUnit: `vendor/bin/phpunit`
- [ ] Run JS tests: `npm run test:unit`
- [ ] Run PHPCS if added (optional YAGNI unless plan extended)
- [ ] **Use @superpowers/verification-before-completion** before claiming done.
- [ ] **Use @superpowers/finishing-a-development-branch** for merge/PR options.

---

## Plan review (Superpowers)

After you finish implementing from this plan, run the review loop from @superpowers/writing-plans: use `skills/writing-plans/plan-document-reviewer-prompt.md` from the Superpowers plugin with inputs:

- Plan path: `docs/superpowers/plans/2026-04-01-query-filter-implementation.md`
- Spec path: `docs/superpowers/specs/2026-04-01-query-filter-design.md`

Fix any issues found; re-review until approved or escalate after three iterations.

---

## Execution handoff

**Plan complete and saved to `docs/superpowers/plans/2026-04-01-query-filter-implementation.md`. Two execution options:**

1. **Subagent-driven (recommended)** — @superpowers/subagent-driven-development: fresh subagent per task, review between tasks.

2. **Inline execution** — @superpowers/executing-plans: run tasks in one session with checkpoints.

**Which approach do you want?**
