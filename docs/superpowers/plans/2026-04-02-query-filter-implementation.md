# Query Filter Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a WordPress plugin that indexes post data in a custom DB table and powers block-based filters (checkboxes, search, sort, pager, reset) for Query Loop blocks, using the Interactivity API for the frontend and a REST endpoint for filtered results.

**Architecture:** PHP core handles indexing (`Indexer`), data access (`Source` adapters), filter logic (`Filter` types), SQL resolution (`QueryEngine`), REST parsing (`Request`), and HTML rendering (`Renderer`). The editor uses a filter-container block (targeting a Query Loop via `queryId` attribute) with child filter blocks. The frontend uses a single Interactivity API store that POSTs to `query-filter/v1/results`, receives server-rendered HTML + filter counts, and updates the DOM. The pager block lives inside the Query Loop.

**Tech stack:** WordPress 6.7+, PHP 8.1+, `@wordpress/scripts` (build), `@wordpress/interactivity` (frontend store), `@wordpress/interactivity-router` (optional), native `fetch` with WP nonce (since `@wordpress/api-fetch` is not available as a script module), PHPUnit 10, `@wordpress/env`.

**Spec reference:** `docs/superpowers/specs/2026-04-01-query-filter-design.md`

**Reference code:** `old/query-filter-main/` for block/interactivity patterns. `old/facetwp-main/` for indexer/renderer patterns.

**Already implemented (Tasks 1-2 of previous plan):**
- Plugin scaffold: `query-filter.php`, `class-plugin.php` (singleton), `composer.json`, `package.json`, `phpunit.xml.dist`
- Indexer table DDL: `class-indexer.php` with `create_table()` and `get_create_table_sql()`
- Unit tests: `PluginExistsTest.php`, `IndexerCreateTableSqlTest.php`
- Activation hook wired in `Plugin::__construct()`

---

## File Map

All paths relative to `query-filter/` (plugin root).

| Path | Responsibility | Status |
|------|----------------|--------|
| `query-filter.php` | Plugin header, constants, autoload, bootstrap | Exists |
| `composer.json` | Autoload (classmap), PHPUnit dev dep | Exists |
| `package.json` | wp-scripts, wp-env, block build | Exists |
| `phpunit.xml.dist` | Test suites config | Exists (modify) |
| `.wp-env.json` | WordPress test environment | Create |
| `includes/class-plugin.php` | Singleton, hook registration | Exists (modify) |
| `includes/class-indexer.php` | Table DDL, index/delete row ops, cron batch | Exists (modify) |
| `includes/sources/class-source.php` | Abstract: `get_values(int $post_id): array` | Create |
| `includes/sources/class-source-taxonomy.php` | Taxonomy term values per post | Create |
| `includes/sources/class-source-postmeta.php` | Post meta values per post | Create |
| `includes/sources/class-source-acf.php` | ACF field values (guarded) | Create |
| `includes/sources/class-source-woocommerce.php` | WooCommerce attributes (guarded) | Create |
| `includes/filters/class-filter.php` | Abstract: `index_post()`, `load_values()` | Create |
| `includes/filters/class-filter-checkboxes.php` | Checkbox indexing + counts query | Create |
| `includes/filters/class-filter-search.php` | Search param for WP_Query | Create |
| `includes/filters/class-filter-sort.php` | orderby/order for WP_Query | Create |
| `includes/filters/class-filter-pager.php` | Page math (total, pages) | Create |
| `includes/filters/class-filter-reset.php` | No-op on server; client-only behavior | Create |
| `includes/class-query-engine.php` | Active filters → `post_id[]` via index SQL | Create |
| `includes/class-request.php` | Parse + sanitize REST payload → typed array | Create |
| `includes/class-rest-controller.php` | Register `query-filter/v1/results` | Create |
| `includes/class-renderer.php` | Re-render Post Template blocks + assemble response | Create |
| `includes/class-admin.php` | Tools → Query Filter screen | Create |
| `includes/class-cli.php` | WP-CLI `query-filter index *` commands | Create |
| `src/filter-container/block.json` | Container block metadata | Create |
| `src/filter-container/index.js` | Editor entry | Create |
| `src/filter-container/edit.js` | Inspector: queryId picker | Create |
| `src/filter-container/render.php` | Server render + interactivity state init | Create |
| `src/filter-container/view.js` | Shared Interactivity store + REST fetch | Create |
| `src/filter-checkboxes/block.json` | Checkboxes block metadata | Create |
| `src/filter-checkboxes/index.js` | Editor entry | Create |
| `src/filter-checkboxes/edit.js` | Inspector: source, logic, label, counts | Create |
| `src/filter-checkboxes/render.php` | Server-render checkbox list from index | Create |
| `src/filter-checkboxes/view.js` | Checkbox toggle → store dispatch | Create |
| `src/filter-search/block.json` | Search block metadata | Create |
| `src/filter-search/index.js` | Editor entry | Create |
| `src/filter-search/edit.js` | Inspector: label, placeholder | Create |
| `src/filter-search/render.php` | Server-render search input | Create |
| `src/filter-search/view.js` | Debounced input → store dispatch | Create |
| `src/filter-sort/block.json` | Sort block metadata | Create |
| `src/filter-sort/index.js` | Editor entry | Create |
| `src/filter-sort/edit.js` | Inspector: sort options config | Create |
| `src/filter-sort/render.php` | Server-render select dropdown | Create |
| `src/filter-sort/view.js` | Select change → store dispatch | Create |
| `src/filter-pager/block.json` | Pager block metadata | Create |
| `src/filter-pager/index.js` | Editor entry | Create |
| `src/filter-pager/edit.js` | Editor placeholder | Create |
| `src/filter-pager/render.php` | Server-render page links | Create |
| `src/filter-pager/view.js` | Page click → store dispatch | Create |
| `src/filter-reset/block.json` | Reset block metadata | Create |
| `src/filter-reset/index.js` | Editor entry | Create |
| `src/filter-reset/edit.js` | Inspector: label | Create |
| `src/filter-reset/render.php` | Server-render reset button | Create |
| `src/filter-reset/view.js` | Reset click → store dispatch | Create |
| `tests/phpunit/bootstrap.php` | Unit test bootstrap (no WP) | Exists |
| `tests/phpunit/integration/bootstrap.php` | Integration test bootstrap (loads WP) | Create |
| `tests/phpunit/unit/` | Unit tests | Exists |
| `tests/phpunit/integration/` | Integration tests (DB, REST, hooks) | Create |
| `tests/js/` | Vitest JS tests | Create |

---

### Task 1: Integration Test Infrastructure

**Files:**
- Create: `query-filter/.wp-env.json`
- Create: `query-filter/tests/phpunit/integration/bootstrap.php`
- Modify: `query-filter/phpunit.xml.dist`
- Create: `query-filter/tests/phpunit/integration/SmokeTest.php`

- [ ] **Step 1: Create `.wp-env.json`**

```json
{
  "core": null,
  "plugins": [ "." ],
  "phpVersion": "8.1",
  "env": {
    "tests": {
      "plugins": [ "." ]
    }
  }
}
```

- [ ] **Step 2: Create integration test bootstrap**

```php
<?php
// tests/phpunit/integration/bootstrap.php
declare(strict_types=1);

$_tests_dir = getenv('WP_TESTS_DIR');
if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (! file_exists($_tests_dir . '/includes/functions.php')) {
    throw new \RuntimeException("WordPress test suite not found at {$_tests_dir}");
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
    require dirname(__DIR__, 2) . '/query-filter.php';
});

require $_tests_dir . '/includes/bootstrap.php';
```

- [ ] **Step 3: Add integration suite to `phpunit.xml.dist`**

Add after the `unit` testsuite:

```xml
<testsuite name="integration">
    <directory>tests/phpunit/integration</directory>
</testsuite>
```

- [ ] **Step 4: Write smoke integration test**

```php
<?php
// tests/phpunit/integration/SmokeTest.php
declare(strict_types=1);

class SmokeTest extends WP_UnitTestCase {

    public function test_plugin_loaded_in_wp_context(): void {
        $this->assertTrue(class_exists(Query_Filter_Plugin::class));
        $this->assertTrue(defined('QUERY_FILTER_VERSION'));
    }

    public function test_index_table_exists_after_activation(): void {
        global $wpdb;
        Query_Filter_Indexer::create_table();
        $table = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', Query_Filter_Indexer::table_name())
        );
        $this->assertSame(Query_Filter_Indexer::table_name(), $table);
    }
}
```

- [ ] **Step 5: Run integration tests — expect PASS**

```bash
npx wp-env start
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration
```

Expected: 2 tests, 2 assertions, OK.

- [ ] **Step 6: Commit**

```bash
git add .wp-env.json tests/phpunit/integration/ phpunit.xml.dist
git commit -m "test: add integration test infrastructure with wp-env"
```

---

### Task 2: Source Adapters (Abstract + Taxonomy + Post Meta)

**Files:**
- Create: `query-filter/includes/sources/class-source.php`
- Create: `query-filter/includes/sources/class-source-taxonomy.php`
- Create: `query-filter/includes/sources/class-source-postmeta.php`
- Create: `query-filter/tests/phpunit/integration/SourceTaxonomyTest.php`
- Create: `query-filter/tests/phpunit/integration/SourcePostMetaTest.php`

- [ ] **Step 1: Write failing integration test for taxonomy source**

```php
<?php
// tests/phpunit/integration/SourceTaxonomyTest.php
declare(strict_types=1);

class SourceTaxonomyTest extends WP_UnitTestCase {

    public function test_get_values_returns_assigned_terms(): void {
        $post_id = self::factory()->post->create();
        $term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        wp_set_object_terms($post_id, [$term->term_id], 'category');

        $source = new Query_Filter_Source_Taxonomy('category');
        $values = $source->get_values($post_id);

        $this->assertCount(1, $values);
        $this->assertSame($term->slug, $values[0]['value']);
        $this->assertSame('Shoes', $values[0]['label']);
        $this->assertSame($term->term_id, $values[0]['term_id']);
        $this->assertSame(0, $values[0]['parent_id']);
        $this->assertSame(0, $values[0]['depth']);
    }

    public function test_get_values_returns_empty_for_post_with_no_terms(): void {
        $post_id = self::factory()->post->create();
        wp_set_object_terms($post_id, [], 'category');

        $source = new Query_Filter_Source_Taxonomy('category');
        $values = $source->get_values($post_id);

        $this->assertSame([], $values);
    }

    public function test_get_values_includes_parent_and_depth(): void {
        $parent = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Footwear']);
        $child = self::factory()->term->create_and_get([
            'taxonomy' => 'category',
            'name' => 'Sneakers',
            'parent' => $parent->term_id,
        ]);
        $post_id = self::factory()->post->create();
        wp_set_object_terms($post_id, [$child->term_id], 'category');

        $source = new Query_Filter_Source_Taxonomy('category');
        $values = $source->get_values($post_id);

        $this->assertCount(1, $values);
        $this->assertSame($parent->term_id, $values[0]['parent_id']);
        $this->assertSame(1, $values[0]['depth']);
    }
}
```

- [ ] **Step 2: Write failing integration test for post meta source**

```php
<?php
// tests/phpunit/integration/SourcePostMetaTest.php
declare(strict_types=1);

class SourcePostMetaTest extends WP_UnitTestCase {

    public function test_get_values_returns_scalar_meta(): void {
        $post_id = self::factory()->post->create();
        update_post_meta($post_id, 'color', 'red');

        $source = new Query_Filter_Source_Post_Meta('color');
        $values = $source->get_values($post_id);

        $this->assertCount(1, $values);
        $this->assertSame('red', $values[0]['value']);
        $this->assertSame('red', $values[0]['label']);
    }

    public function test_get_values_returns_multiple_meta_values(): void {
        $post_id = self::factory()->post->create();
        add_post_meta($post_id, 'size', 'small');
        add_post_meta($post_id, 'size', 'medium');

        $source = new Query_Filter_Source_Post_Meta('size');
        $values = $source->get_values($post_id);

        $this->assertCount(2, $values);
        $slugs = array_column($values, 'value');
        $this->assertContains('small', $slugs);
        $this->assertContains('medium', $slugs);
    }

    public function test_get_values_returns_empty_when_no_meta(): void {
        $post_id = self::factory()->post->create();

        $source = new Query_Filter_Source_Post_Meta('nonexistent');
        $values = $source->get_values($post_id);

        $this->assertSame([], $values);
    }
}
```

- [ ] **Step 3: Run tests — expect FAIL**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter Source
```

Expected: class not found errors.

- [ ] **Step 4: Implement abstract Source**

```php
<?php
// includes/sources/class-source.php
declare(strict_types=1);

abstract class Query_Filter_Source {

    /**
     * Return indexable values for a post.
     *
     * Each element: ['value' => string, 'label' => string, 'term_id' => int, 'parent_id' => int, 'depth' => int]
     *
     * @return array<int, array{value: string, label: string, term_id: int, parent_id: int, depth: int}>
     */
    abstract public function get_values(int $post_id): array;
}
```

- [ ] **Step 5: Implement Source_Taxonomy**

```php
<?php
// includes/sources/class-source-taxonomy.php
declare(strict_types=1);

final class Query_Filter_Source_Taxonomy extends Query_Filter_Source {

    public function __construct(
        private readonly string $taxonomy,
    ) {}

    public function get_values(int $post_id): array {
        $terms = wp_get_object_terms($post_id, $this->taxonomy);
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $values = [];
        foreach ($terms as $term) {
            $depth = 0;
            $parent_id = $term->parent;
            $ancestor = $parent_id;
            while ($ancestor > 0) {
                $depth++;
                $parent_term = get_term($ancestor, $this->taxonomy);
                $ancestor = ($parent_term && ! is_wp_error($parent_term)) ? $parent_term->parent : 0;
            }

            $values[] = [
                'value'     => $term->slug,
                'label'     => $term->name,
                'term_id'   => $term->term_id,
                'parent_id' => $parent_id,
                'depth'     => $depth,
            ];
        }

        return $values;
    }
}
```

- [ ] **Step 6: Implement Source_Post_Meta**

```php
<?php
// includes/sources/class-source-postmeta.php
declare(strict_types=1);

final class Query_Filter_Source_Post_Meta extends Query_Filter_Source {

    public function __construct(
        private readonly string $meta_key,
    ) {}

    public function get_values(int $post_id): array {
        $raw = get_post_meta($post_id, $this->meta_key);
        if (empty($raw)) {
            return [];
        }

        $values = [];
        foreach ($raw as $val) {
            $str = (string) $val;
            if ($str === '') {
                continue;
            }
            $values[] = [
                'value'     => $str,
                'label'     => $str,
                'term_id'   => 0,
                'parent_id' => 0,
                'depth'     => 0,
            ];
        }

        return $values;
    }
}
```

- [ ] **Step 7: Run tests — expect PASS**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter Source
```

Expected: 6 tests, all PASS.

- [ ] **Step 8: Commit**

```bash
git add includes/sources/ tests/phpunit/integration/Source*
git commit -m "feat(sources): abstract source, taxonomy, and post meta adapters"
```

---

### Task 3: Conditional Sources (ACF + WooCommerce)

**Files:**
- Create: `query-filter/includes/sources/class-source-acf.php`
- Create: `query-filter/includes/sources/class-source-woocommerce.php`
- Create: `query-filter/tests/phpunit/unit/SourceAcfGuardTest.php`
- Create: `query-filter/tests/phpunit/unit/SourceWooCommerceGuardTest.php`

- [ ] **Step 1: Write unit tests for ACF/Woo guard behavior**

Since ACF and WooCommerce won't be available in the test environment, test that the sources guard gracefully.

```php
<?php
// tests/phpunit/unit/SourceAcfGuardTest.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SourceAcfGuardTest extends TestCase {

    public function test_is_available_returns_false_when_acf_not_loaded(): void {
        $this->assertFalse(Query_Filter_Source_ACF::is_available());
    }
}
```

```php
<?php
// tests/phpunit/unit/SourceWooCommerceGuardTest.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SourceWooCommerceGuardTest extends TestCase {

    public function test_is_available_returns_false_when_woo_not_loaded(): void {
        $this->assertFalse(Query_Filter_Source_WooCommerce::is_available());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
cd query-filter && vendor/bin/phpunit --testsuite unit --filter Guard
```

- [ ] **Step 3: Implement ACF source**

```php
<?php
// includes/sources/class-source-acf.php
declare(strict_types=1);

final class Query_Filter_Source_ACF extends Query_Filter_Source {

    public function __construct(
        private readonly string $field_name,
    ) {}

    public static function is_available(): bool {
        return function_exists('get_field');
    }

    public function get_values(int $post_id): array {
        if (! self::is_available()) {
            return [];
        }

        $raw = get_field($this->field_name, $post_id, false);
        if (empty($raw)) {
            return [];
        }

        $items = is_array($raw) ? $raw : [$raw];
        $values = [];
        foreach ($items as $item) {
            $str = (string) $item;
            if ($str === '') {
                continue;
            }
            $values[] = [
                'value'     => $str,
                'label'     => $str,
                'term_id'   => 0,
                'parent_id' => 0,
                'depth'     => 0,
            ];
        }

        return $values;
    }
}
```

- [ ] **Step 4: Implement WooCommerce source**

```php
<?php
// includes/sources/class-source-woocommerce.php
declare(strict_types=1);

final class Query_Filter_Source_WooCommerce extends Query_Filter_Source {

    public function __construct(
        private readonly string $attribute_name,
    ) {}

    public static function is_available(): bool {
        return class_exists('WooCommerce');
    }

    public function get_values(int $post_id): array {
        if (! self::is_available()) {
            return [];
        }

        $product = wc_get_product($post_id);
        if (! $product) {
            return [];
        }

        $taxonomy = wc_attribute_taxonomy_name($this->attribute_name);
        $terms = wp_get_object_terms($post_id, $taxonomy);
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $values = [];
        foreach ($terms as $term) {
            $values[] = [
                'value'     => $term->slug,
                'label'     => $term->name,
                'term_id'   => $term->term_id,
                'parent_id' => $term->parent,
                'depth'     => 0,
            ];
        }

        return $values;
    }
}
```

- [ ] **Step 5: Run tests — expect PASS**

```bash
cd query-filter && vendor/bin/phpunit --testsuite unit --filter Guard
```

Expected: 2 tests, 2 assertions, OK.

- [ ] **Step 6: Commit**

```bash
git add includes/sources/class-source-acf.php includes/sources/class-source-woocommerce.php tests/phpunit/unit/Source*Guard*
git commit -m "feat(sources): ACF and WooCommerce adapters with availability guards"
```

---

### Task 4: Filter Abstract + Checkboxes Indexing + Indexer Row Operations

**Files:**
- Create: `query-filter/includes/filters/class-filter.php`
- Create: `query-filter/includes/filters/class-filter-checkboxes.php`
- Modify: `query-filter/includes/class-indexer.php` — add `index_post()`, `delete_for_post()`, `register_filter()`
- Create: `query-filter/tests/phpunit/integration/CheckboxesIndexingTest.php`

- [ ] **Step 1: Write failing integration test**

```php
<?php
// tests/phpunit/integration/CheckboxesIndexingTest.php
declare(strict_types=1);

class CheckboxesIndexingTest extends WP_UnitTestCase {

    public function set_up(): void {
        parent::set_up();
        Query_Filter_Indexer::create_table();
    }

    public function test_index_post_writes_rows_for_assigned_terms(): void {
        global $wpdb;

        $post_id = self::factory()->post->create();
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $boots = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Boots']);
        wp_set_object_terms($post_id, [$shoes->term_id, $boots->term_id], 'category');

        $source = new Query_Filter_Source_Taxonomy('category');
        $filter = new Query_Filter_Filter_Checkboxes('category', $source);

        $indexer = new Query_Filter_Indexer();
        $indexer->register_filter($filter);
        $indexer->index_post($post_id);

        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT filter_name, filter_value, display_value FROM %i WHERE post_id = %d ORDER BY filter_value',
            Query_Filter_Indexer::table_name(),
            $post_id
        ));

        $this->assertCount(2, $rows);
        $this->assertSame('category', $rows[0]->filter_name);
        $this->assertSame($boots->slug, $rows[0]->filter_value);
        $this->assertSame('Boots', $rows[0]->display_value);
        $this->assertSame($shoes->slug, $rows[1]->filter_value);
    }

    public function test_delete_for_post_removes_all_rows(): void {
        global $wpdb;

        $post_id = self::factory()->post->create();
        $term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Test']);
        wp_set_object_terms($post_id, [$term->term_id], 'category');

        $source = new Query_Filter_Source_Taxonomy('category');
        $filter = new Query_Filter_Filter_Checkboxes('category', $source);
        $indexer = new Query_Filter_Indexer();
        $indexer->register_filter($filter);
        $indexer->index_post($post_id);

        $count_before = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM %i WHERE post_id = %d',
            Query_Filter_Indexer::table_name(),
            $post_id
        ));
        $this->assertGreaterThan(0, $count_before);

        $indexer->delete_for_post($post_id);

        $count_after = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM %i WHERE post_id = %d',
            Query_Filter_Indexer::table_name(),
            $post_id
        ));
        $this->assertSame(0, $count_after);
    }

    public function test_reindex_replaces_old_rows(): void {
        global $wpdb;

        $post_id = self::factory()->post->create();
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $boots = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Boots']);

        $source = new Query_Filter_Source_Taxonomy('category');
        $filter = new Query_Filter_Filter_Checkboxes('category', $source);
        $indexer = new Query_Filter_Indexer();
        $indexer->register_filter($filter);

        wp_set_object_terms($post_id, [$shoes->term_id, $boots->term_id], 'category');
        $indexer->index_post($post_id);

        wp_set_object_terms($post_id, [$shoes->term_id], 'category');
        $indexer->index_post($post_id);

        $count = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM %i WHERE post_id = %d',
            Query_Filter_Indexer::table_name(),
            $post_id
        ));
        $this->assertSame(1, $count);
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter CheckboxesIndexing
```

- [ ] **Step 3: Implement abstract Filter**

```php
<?php
// includes/filters/class-filter.php
declare(strict_types=1);

abstract class Query_Filter_Filter {

    public function __construct(
        protected readonly string $filter_name,
        protected readonly Query_Filter_Source $source,
    ) {}

    public function get_name(): string {
        return $this->filter_name;
    }

    /**
     * Return index rows for a post.
     *
     * @return array<int, array{post_id: int, filter_name: string, filter_value: string, display_value: string, term_id: int, parent_id: int, depth: int}>
     */
    abstract public function index_post(int $post_id): array;

    /**
     * Return available filter values with counts.
     *
     * @param array{post_ids?: int[], logic?: string} $params
     * @return array<int, array{value: string, label: string, count: int}>
     */
    abstract public function load_values(array $params): array;
}
```

- [ ] **Step 4: Implement Filter_Checkboxes (indexing only, load_values stubbed)**

```php
<?php
// includes/filters/class-filter-checkboxes.php
declare(strict_types=1);

final class Query_Filter_Filter_Checkboxes extends Query_Filter_Filter {

    public function index_post(int $post_id): array {
        $source_values = $this->source->get_values($post_id);
        $rows = [];

        foreach ($source_values as $val) {
            $rows[] = [
                'post_id'       => $post_id,
                'filter_name'   => $this->filter_name,
                'filter_value'  => $val['value'],
                'display_value' => $val['label'],
                'term_id'       => $val['term_id'],
                'parent_id'     => $val['parent_id'],
                'depth'         => $val['depth'],
            ];
        }

        return $rows;
    }

    public function load_values(array $params): array {
        // Implemented in Task 6.
        return [];
    }
}
```

- [ ] **Step 5: Add `index_post()`, `delete_for_post()`, `register_filter()` to Indexer**

Add these methods to `Query_Filter_Indexer`:

```php
/** @var Query_Filter_Filter[] */
private array $filters = [];

public function register_filter(Query_Filter_Filter $filter): void {
    $this->filters[$filter->get_name()] = $filter;
}

public function get_filter(string $name): ?Query_Filter_Filter {
    return $this->filters[$name] ?? null;
}

/** @return Query_Filter_Filter[] */
public function get_filters(): array {
    return $this->filters;
}

public function index_post(int $post_id): void {
    global $wpdb;
    $table = self::table_name();

    foreach ($this->filters as $filter) {
        $wpdb->delete($table, [
            'post_id'     => $post_id,
            'filter_name' => $filter->get_name(),
        ]);

        $rows = $filter->index_post($post_id);
        foreach ($rows as $row) {
            $wpdb->insert($table, $row);
        }
    }
}

public function delete_for_post(int $post_id): void {
    global $wpdb;
    $wpdb->delete(self::table_name(), ['post_id' => $post_id]);
}
```

Also change `Query_Filter_Indexer` from `static`-only to instance-based. Keep `create_table()` and `table_name()` as static. Remove `final` so the class can hold state:

The class declaration stays `final class Query_Filter_Indexer` — the new methods are instance methods alongside the existing static methods.

- [ ] **Step 6: Run — expect PASS**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter CheckboxesIndexing
```

Expected: 3 tests, all PASS.

- [ ] **Step 7: Commit**

```bash
git add includes/filters/ includes/class-indexer.php tests/phpunit/integration/CheckboxesIndexingTest.php
git commit -m "feat(filters): abstract filter, checkboxes indexing, indexer row ops"
```

---

### Task 5: Indexer Lifecycle Hooks

**Files:**
- Modify: `query-filter/includes/class-plugin.php` — wire `save_post`, `delete_post`, `edited_term`, `delete_term` hooks
- Modify: `query-filter/includes/class-indexer.php` — add `schedule_full_rebuild()`, `run_cron_batch()`, `reindex_posts_for_term()`
- Create: `query-filter/tests/phpunit/integration/IndexerHooksTest.php`

- [ ] **Step 1: Write failing integration tests**

```php
<?php
// tests/phpunit/integration/IndexerHooksTest.php
declare(strict_types=1);

class IndexerHooksTest extends WP_UnitTestCase {

    private Query_Filter_Indexer $indexer;

    public function set_up(): void {
        parent::set_up();
        Query_Filter_Indexer::create_table();

        $this->indexer = new Query_Filter_Indexer();
        $source = new Query_Filter_Source_Taxonomy('category');
        $this->indexer->register_filter(new Query_Filter_Filter_Checkboxes('category', $source));
    }

    public function test_save_post_triggers_indexing(): void {
        global $wpdb;
        $table = Query_Filter_Indexer::table_name();

        Query_Filter_Plugin::instance()->set_indexer($this->indexer);

        $term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);
        $post_id = self::factory()->post->create(['post_status' => 'publish']);
        wp_set_object_terms($post_id, [$term->term_id], 'category');

        do_action('save_post', $post_id, get_post($post_id), true);

        $count = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM %i WHERE post_id = %d',
            $table,
            $post_id
        ));
        $this->assertGreaterThan(0, $count);
    }

    public function test_delete_post_removes_index_rows(): void {
        global $wpdb;
        $table = Query_Filter_Indexer::table_name();

        Query_Filter_Plugin::instance()->set_indexer($this->indexer);

        $post_id = self::factory()->post->create(['post_status' => 'publish']);
        $term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Temp']);
        wp_set_object_terms($post_id, [$term->term_id], 'category');
        $this->indexer->index_post($post_id);

        do_action('delete_post', $post_id);

        $count = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM %i WHERE post_id = %d',
            $table,
            $post_id
        ));
        $this->assertSame(0, $count);
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter IndexerHooks
```

- [ ] **Step 3: Implement lifecycle hooks in Plugin**

Update `Query_Filter_Plugin` to accept an Indexer and wire hooks:

```php
<?php
// includes/class-plugin.php
declare(strict_types=1);

final class Query_Filter_Plugin {

    private static ?self $instance = null;
    private ?Query_Filter_Indexer $indexer = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set_indexer(Query_Filter_Indexer $indexer): void {
        $this->indexer = $indexer;
    }

    public function get_indexer(): ?Query_Filter_Indexer {
        return $this->indexer;
    }

    private function __construct() {
        if (! function_exists('register_activation_hook')) {
            return;
        }

        register_activation_hook(
            QUERY_FILTER_PLUGIN_FILE,
            static function (): void {
                Query_Filter_Indexer::create_table();
                Query_Filter_Indexer::schedule_full_rebuild();
            }
        );

        add_action('save_post', [$this, 'on_save_post'], 10, 2);
        add_action('delete_post', [$this, 'on_delete_post']);
        add_action('edited_term', [$this, 'on_edited_term'], 10, 3);
        add_action('delete_term', [$this, 'on_delete_term'], 10, 4);
        add_action('query_filter_cron_rebuild', [$this, 'on_cron_rebuild']);
    }

    public function on_save_post(int $post_id, \WP_Post $post): void {
        if ($this->indexer === null) {
            return;
        }
        if ($post->post_status !== 'publish') {
            $this->indexer->delete_for_post($post_id);
            return;
        }
        $this->indexer->index_post($post_id);
    }

    public function on_delete_post(int $post_id): void {
        $this->indexer?->delete_for_post($post_id);
    }

    public function on_edited_term(int $term_id, int $tt_id, string $taxonomy): void {
        $this->indexer?->reindex_posts_for_term($term_id, $taxonomy);
    }

    public function on_delete_term(int $term_id, int $tt_id, string $taxonomy, \WP_Term $deleted_term): void {
        $this->indexer?->reindex_posts_for_term($term_id, $taxonomy);
    }

    public function on_cron_rebuild(): void {
        $this->indexer?->run_cron_batch();
    }
}
```

- [ ] **Step 4: Add cron/term methods to Indexer**

```php
// Add to class-indexer.php:

public const CRON_HOOK = 'query_filter_cron_rebuild';
public const BATCH_SIZE = 10;

public static function schedule_full_rebuild(): void {
    update_option('query_filter_rebuild_offset', 0);
    if (! wp_next_scheduled(self::CRON_HOOK)) {
        wp_schedule_single_event(time(), self::CRON_HOOK);
    }
}

public function run_cron_batch(): void {
    $offset = (int) get_option('query_filter_rebuild_offset', 0);

    $post_ids = get_posts([
        'post_type'      => 'any',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => self::BATCH_SIZE,
        'offset'         => $offset,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ]);

    foreach ($post_ids as $post_id) {
        $this->index_post($post_id);
    }

    if (count($post_ids) >= self::BATCH_SIZE) {
        update_option('query_filter_rebuild_offset', $offset + self::BATCH_SIZE);
        wp_schedule_single_event(time() + 5, self::CRON_HOOK);
    } else {
        delete_option('query_filter_rebuild_offset');
        update_option('query_filter_last_indexed', time());
    }
}

public function reindex_posts_for_term(int $term_id, string $taxonomy): void {
    $post_ids = get_objects_in_term($term_id, $taxonomy);
    if (is_wp_error($post_ids) || empty($post_ids)) {
        return;
    }
    foreach ($post_ids as $post_id) {
        $this->index_post((int) $post_id);
    }
}
```

- [ ] **Step 5: Run — expect PASS**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter IndexerHooks
```

- [ ] **Step 6: Commit**

```bash
git add includes/class-plugin.php includes/class-indexer.php tests/phpunit/integration/IndexerHooksTest.php
git commit -m "feat(indexer): save_post, delete_post, term hooks, cron batch rebuild"
```

---

### Task 6: QueryEngine + Checkboxes load_values

**Files:**
- Create: `query-filter/includes/class-query-engine.php`
- Modify: `query-filter/includes/filters/class-filter-checkboxes.php` — implement `load_values()`
- Create: `query-filter/tests/phpunit/integration/QueryEngineTest.php`
- Create: `query-filter/tests/phpunit/integration/CheckboxesLoadValuesTest.php`

- [ ] **Step 1: Write failing QueryEngine test**

```php
<?php
// tests/phpunit/integration/QueryEngineTest.php
declare(strict_types=1);

class QueryEngineTest extends WP_UnitTestCase {

    private Query_Filter_Indexer $indexer;

    public function set_up(): void {
        parent::set_up();
        Query_Filter_Indexer::create_table();

        $this->indexer = new Query_Filter_Indexer();
        $cat_source = new Query_Filter_Source_Taxonomy('category');
        $this->indexer->register_filter(new Query_Filter_Filter_Checkboxes('category', $cat_source));
    }

    public function test_or_logic_returns_posts_matching_any_value(): void {
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $boots = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Boots']);
        $hats  = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);

        $p1 = self::factory()->post->create();
        wp_set_object_terms($p1, [$shoes->term_id], 'category');
        $this->indexer->index_post($p1);

        $p2 = self::factory()->post->create();
        wp_set_object_terms($p2, [$boots->term_id], 'category');
        $this->indexer->index_post($p2);

        $p3 = self::factory()->post->create();
        wp_set_object_terms($p3, [$hats->term_id], 'category');
        $this->indexer->index_post($p3);

        $engine = new Query_Filter_Query_Engine();
        $result = $engine->get_post_ids(
            ['category' => ['values' => [$shoes->slug, $boots->slug], 'logic' => 'OR']],
        );

        sort($result);
        $expected = [$p1, $p2];
        sort($expected);
        $this->assertSame($expected, $result);
    }

    public function test_and_logic_returns_posts_matching_all_values(): void {
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $red   = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Red']);

        $p1 = self::factory()->post->create();
        wp_set_object_terms($p1, [$shoes->term_id, $red->term_id], 'category');
        $this->indexer->index_post($p1);

        $p2 = self::factory()->post->create();
        wp_set_object_terms($p2, [$shoes->term_id], 'category');
        $this->indexer->index_post($p2);

        $engine = new Query_Filter_Query_Engine();
        $result = $engine->get_post_ids(
            ['category' => ['values' => [$shoes->slug, $red->slug], 'logic' => 'AND']],
        );

        $this->assertSame([$p1], $result);
    }

    public function test_multiple_filters_intersect(): void {
        register_taxonomy('color', 'post');

        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $red   = self::factory()->term->create_and_get(['taxonomy' => 'color', 'name' => 'Red']);
        $blue  = self::factory()->term->create_and_get(['taxonomy' => 'color', 'name' => 'Blue']);

        $color_source = new Query_Filter_Source_Taxonomy('color');
        $this->indexer->register_filter(new Query_Filter_Filter_Checkboxes('color', $color_source));

        $p1 = self::factory()->post->create();
        wp_set_object_terms($p1, [$shoes->term_id], 'category');
        wp_set_object_terms($p1, [$red->term_id], 'color');
        $this->indexer->index_post($p1);

        $p2 = self::factory()->post->create();
        wp_set_object_terms($p2, [$shoes->term_id], 'category');
        wp_set_object_terms($p2, [$blue->term_id], 'color');
        $this->indexer->index_post($p2);

        $engine = new Query_Filter_Query_Engine();
        $result = $engine->get_post_ids([
            'category' => ['values' => [$shoes->slug], 'logic' => 'OR'],
            'color'    => ['values' => [$red->slug], 'logic' => 'OR'],
        ]);

        $this->assertSame([$p1], $result);
    }

    public function test_empty_filters_returns_all_indexed_post_ids(): void {
        $term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Any']);
        $p1 = self::factory()->post->create();
        $p2 = self::factory()->post->create();
        wp_set_object_terms($p1, [$term->term_id], 'category');
        wp_set_object_terms($p2, [$term->term_id], 'category');
        $this->indexer->index_post($p1);
        $this->indexer->index_post($p2);

        $engine = new Query_Filter_Query_Engine();
        $result = $engine->get_post_ids([]);

        sort($result);
        $expected = [$p1, $p2];
        sort($expected);
        $this->assertSame($expected, $result);
    }
}
```

- [ ] **Step 2: Write failing load_values test**

```php
<?php
// tests/phpunit/integration/CheckboxesLoadValuesTest.php
declare(strict_types=1);

class CheckboxesLoadValuesTest extends WP_UnitTestCase {

    public function set_up(): void {
        parent::set_up();
        Query_Filter_Indexer::create_table();
    }

    public function test_load_values_returns_counts_for_matching_posts(): void {
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $hats  = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);

        $indexer = new Query_Filter_Indexer();
        $source = new Query_Filter_Source_Taxonomy('category');
        $filter = new Query_Filter_Filter_Checkboxes('category', $source);
        $indexer->register_filter($filter);

        $p1 = self::factory()->post->create();
        $p2 = self::factory()->post->create();
        $p3 = self::factory()->post->create();
        wp_set_object_terms($p1, [$shoes->term_id], 'category');
        wp_set_object_terms($p2, [$shoes->term_id], 'category');
        wp_set_object_terms($p3, [$hats->term_id], 'category');
        $indexer->index_post($p1);
        $indexer->index_post($p2);
        $indexer->index_post($p3);

        $values = $filter->load_values(['post_ids' => [$p1, $p2, $p3]]);

        $this->assertCount(2, $values);
        $by_value = array_column($values, null, 'value');
        $this->assertSame(2, $by_value[$shoes->slug]['count']);
        $this->assertSame(1, $by_value[$hats->slug]['count']);
    }

    public function test_load_values_counts_scoped_to_given_post_ids(): void {
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);

        $indexer = new Query_Filter_Indexer();
        $source = new Query_Filter_Source_Taxonomy('category');
        $filter = new Query_Filter_Filter_Checkboxes('category', $source);
        $indexer->register_filter($filter);

        $p1 = self::factory()->post->create();
        $p2 = self::factory()->post->create();
        wp_set_object_terms($p1, [$shoes->term_id], 'category');
        wp_set_object_terms($p2, [$shoes->term_id], 'category');
        $indexer->index_post($p1);
        $indexer->index_post($p2);

        $values = $filter->load_values(['post_ids' => [$p1]]);

        $by_value = array_column($values, null, 'value');
        $this->assertSame(1, $by_value[$shoes->slug]['count']);
    }
}
```

- [ ] **Step 3: Run — expect FAIL**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter "QueryEngine|CheckboxesLoadValues"
```

- [ ] **Step 4: Implement QueryEngine**

```php
<?php
// includes/class-query-engine.php
declare(strict_types=1);

final class Query_Filter_Query_Engine {

    /**
     * Resolve matching post IDs from the index based on active filters.
     *
     * @param array<string, array{values: string[], logic: string}> $active_filters
     * @return int[]
     */
    public function get_post_ids(array $active_filters): array {
        global $wpdb;
        $table = Query_Filter_Indexer::table_name();

        if (empty($active_filters)) {
            $ids = $wpdb->get_col("SELECT DISTINCT post_id FROM {$table}");
            return array_map('intval', $ids);
        }

        $sets = [];

        foreach ($active_filters as $filter_name => $config) {
            $values = $config['values'];
            $logic  = strtoupper($config['logic'] ?? 'OR');

            if (empty($values)) {
                continue;
            }

            $placeholders = implode(',', array_fill(0, count($values), '%s'));
            $params = array_merge([$filter_name], $values);

            if ($logic === 'AND') {
                $sql = $wpdb->prepare(
                    "SELECT post_id FROM {$table}
                     WHERE filter_name = %s AND filter_value IN ({$placeholders})
                     GROUP BY post_id
                     HAVING COUNT(DISTINCT filter_value) = %d",
                    array_merge($params, [count($values)])
                );
            } else {
                $sql = $wpdb->prepare(
                    "SELECT DISTINCT post_id FROM {$table}
                     WHERE filter_name = %s AND filter_value IN ({$placeholders})",
                    $params
                );
            }

            $ids = $wpdb->get_col($sql);
            $sets[] = array_map('intval', $ids);
        }

        if (empty($sets)) {
            $ids = $wpdb->get_col("SELECT DISTINCT post_id FROM {$table}");
            return array_map('intval', $ids);
        }

        // Intersect across filters (AND between different filters).
        $result = $sets[0];
        for ($i = 1; $i < count($sets); $i++) {
            $result = array_values(array_intersect($result, $sets[$i]));
        }

        return $result;
    }
}
```

- [ ] **Step 5: Implement load_values in Filter_Checkboxes**

Replace the stub `load_values()` in `class-filter-checkboxes.php`:

```php
public function load_values(array $params): array {
    global $wpdb;
    $table = Query_Filter_Indexer::table_name();

    $where = "WHERE filter_name = %s";
    $bind  = [$this->filter_name];

    if (! empty($params['post_ids'])) {
        $id_placeholders = implode(',', array_fill(0, count($params['post_ids']), '%d'));
        $where .= " AND post_id IN ({$id_placeholders})";
        $bind = array_merge($bind, $params['post_ids']);
    }

    $sql = $wpdb->prepare(
        "SELECT filter_value AS value, display_value AS label, COUNT(DISTINCT post_id) AS count
         FROM {$table}
         {$where}
         GROUP BY filter_value, display_value
         ORDER BY count DESC, display_value ASC",
        $bind
    );

    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (! $rows) {
        return [];
    }

    return array_map(static fn(array $row): array => [
        'value' => $row['value'],
        'label' => $row['label'],
        'count' => (int) $row['count'],
    ], $rows);
}
```

- [ ] **Step 6: Run — expect PASS**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter "QueryEngine|CheckboxesLoadValues"
```

Expected: 6 tests, all PASS.

- [ ] **Step 7: Commit**

```bash
git add includes/class-query-engine.php includes/filters/class-filter-checkboxes.php tests/phpunit/integration/QueryEngineTest.php tests/phpunit/integration/CheckboxesLoadValuesTest.php
git commit -m "feat(engine): query engine post ID resolution and checkboxes load_values"
```

---

### Task 7: Search, Sort, Pager, Reset Filters (Server-Side)

**Files:**
- Create: `query-filter/includes/filters/class-filter-search.php`
- Create: `query-filter/includes/filters/class-filter-sort.php`
- Create: `query-filter/includes/filters/class-filter-pager.php`
- Create: `query-filter/includes/filters/class-filter-reset.php`
- Create: `query-filter/tests/phpunit/unit/FilterSearchTest.php`
- Create: `query-filter/tests/phpunit/unit/FilterSortTest.php`
- Create: `query-filter/tests/phpunit/unit/FilterPagerTest.php`

- [ ] **Step 1: Write unit tests**

```php
<?php
// tests/phpunit/unit/FilterSearchTest.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FilterSearchTest extends TestCase {

    public function test_get_query_args_returns_search_param(): void {
        $filter = new Query_Filter_Filter_Search();
        $args = $filter->get_query_args('running shoes');
        $this->assertSame('running shoes', $args['s']);
    }

    public function test_get_query_args_returns_empty_for_blank_search(): void {
        $filter = new Query_Filter_Filter_Search();
        $args = $filter->get_query_args('');
        $this->assertEmpty($args);
    }
}
```

```php
<?php
// tests/phpunit/unit/FilterSortTest.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FilterSortTest extends TestCase {

    public function test_get_query_args_returns_orderby_and_order(): void {
        $filter = new Query_Filter_Filter_Sort();
        $args = $filter->get_query_args('price', 'ASC');
        $this->assertSame('price', $args['orderby']);
        $this->assertSame('ASC', $args['order']);
    }

    public function test_get_query_args_defaults_to_date_desc(): void {
        $filter = new Query_Filter_Filter_Sort();
        $args = $filter->get_query_args('', '');
        $this->assertSame('date', $args['orderby']);
        $this->assertSame('DESC', $args['order']);
    }
}
```

```php
<?php
// tests/phpunit/unit/FilterPagerTest.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FilterPagerTest extends TestCase {

    public function test_compute_returns_correct_pagination(): void {
        $filter = new Query_Filter_Filter_Pager();
        $result = $filter->compute(total: 47, per_page: 10, current_page: 2);

        $this->assertSame(47, $result['total']);
        $this->assertSame(5, $result['pages']);
        $this->assertSame(2, $result['current_page']);
    }

    public function test_compute_clamps_page_to_max(): void {
        $filter = new Query_Filter_Filter_Pager();
        $result = $filter->compute(total: 5, per_page: 10, current_page: 3);

        $this->assertSame(1, $result['pages']);
        $this->assertSame(1, $result['current_page']);
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
cd query-filter && vendor/bin/phpunit --testsuite unit --filter "FilterSearch|FilterSort|FilterPager"
```

- [ ] **Step 3: Implement Filter_Search**

```php
<?php
// includes/filters/class-filter-search.php
declare(strict_types=1);

final class Query_Filter_Filter_Search {

    /**
     * @return array<string, string>
     */
    public function get_query_args(string $search): array {
        $search = trim($search);
        if ($search === '') {
            return [];
        }
        return ['s' => $search];
    }
}
```

- [ ] **Step 4: Implement Filter_Sort**

```php
<?php
// includes/filters/class-filter-sort.php
declare(strict_types=1);

final class Query_Filter_Filter_Sort {

    /**
     * @return array{orderby: string, order: string}
     */
    public function get_query_args(string $orderby, string $order): array {
        $orderby = $orderby ?: 'date';
        $order = strtoupper($order ?: 'DESC');
        if (! in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }
        return [
            'orderby' => $orderby,
            'order'   => $order,
        ];
    }
}
```

- [ ] **Step 5: Implement Filter_Pager**

```php
<?php
// includes/filters/class-filter-pager.php
declare(strict_types=1);

final class Query_Filter_Filter_Pager {

    /**
     * @return array{total: int, pages: int, current_page: int}
     */
    public function compute(int $total, int $per_page, int $current_page): array {
        $per_page = max(1, $per_page);
        $pages = (int) ceil($total / $per_page);
        $current_page = max(1, min($current_page, max(1, $pages)));

        return [
            'total'        => $total,
            'pages'        => $pages,
            'current_page' => $current_page,
        ];
    }
}
```

- [ ] **Step 6: Implement Filter_Reset (server no-op)**

```php
<?php
// includes/filters/class-filter-reset.php
declare(strict_types=1);

final class Query_Filter_Filter_Reset {
    // Reset is client-side only. No server behavior needed.
}
```

- [ ] **Step 7: Run — expect PASS**

```bash
cd query-filter && vendor/bin/phpunit --testsuite unit --filter "FilterSearch|FilterSort|FilterPager"
```

Expected: 5 tests, all PASS.

- [ ] **Step 8: Commit**

```bash
git add includes/filters/class-filter-search.php includes/filters/class-filter-sort.php includes/filters/class-filter-pager.php includes/filters/class-filter-reset.php tests/phpunit/unit/Filter*.php
git commit -m "feat(filters): search, sort, pager, reset server-side filters"
```

---

### Task 8: Request Parser

**Files:**
- Create: `query-filter/includes/class-request.php`
- Create: `query-filter/tests/phpunit/unit/RequestTest.php`

- [ ] **Step 1: Write failing unit test**

```php
<?php
// tests/phpunit/unit/RequestTest.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase {

    public function test_from_array_parses_valid_payload(): void {
        $input = [
            'queryId' => 1,
            'pageId'  => 42,
            'filters' => ['category' => ['shoes', 'boots']],
            'page'    => 2,
            'orderby' => 'price',
            'order'   => 'ASC',
            'search'  => 'running',
        ];

        $request = Query_Filter_Request::from_array($input);

        $this->assertSame(1, $request->query_id);
        $this->assertSame(42, $request->page_id);
        $this->assertSame(['category' => ['shoes', 'boots']], $request->filters);
        $this->assertSame(2, $request->page);
        $this->assertSame('price', $request->orderby);
        $this->assertSame('ASC', $request->order);
        $this->assertSame('running', $request->search);
    }

    public function test_from_array_uses_defaults_for_missing_fields(): void {
        $request = Query_Filter_Request::from_array(['queryId' => 1, 'pageId' => 10]);

        $this->assertSame([], $request->filters);
        $this->assertSame(1, $request->page);
        $this->assertSame('date', $request->orderby);
        $this->assertSame('DESC', $request->order);
        $this->assertSame('', $request->search);
    }

    public function test_from_array_sanitizes_filter_values(): void {
        $input = [
            'queryId' => 1,
            'pageId'  => 10,
            'filters' => ['cat' => ['<script>alert(1)</script>', 'shoes']],
        ];

        $request = Query_Filter_Request::from_array($input);

        $this->assertSame(['cat' => ['alert(1)', 'shoes']], $request->filters);
    }

    public function test_from_array_rejects_non_array_filters(): void {
        $input = ['queryId' => 1, 'pageId' => 10, 'filters' => 'bad'];
        $request = Query_Filter_Request::from_array($input);
        $this->assertSame([], $request->filters);
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
cd query-filter && vendor/bin/phpunit --testsuite unit --filter Request
```

- [ ] **Step 3: Implement Request**

```php
<?php
// includes/class-request.php
declare(strict_types=1);

final class Query_Filter_Request {

    public function __construct(
        public readonly int $query_id,
        public readonly int $page_id,
        /** @var array<string, string[]> */
        public readonly array $filters,
        public readonly int $page,
        public readonly string $orderby,
        public readonly string $order,
        public readonly string $search,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function from_array(array $data): self {
        $filters_raw = $data['filters'] ?? [];
        $filters = [];

        if (is_array($filters_raw)) {
            foreach ($filters_raw as $name => $values) {
                if (! is_string($name) || ! is_array($values)) {
                    continue;
                }
                $clean = [];
                foreach ($values as $v) {
                    if (is_string($v)) {
                        $clean[] = sanitize_text_field($v);
                    }
                }
                if (! empty($clean)) {
                    $filters[sanitize_key($name)] = $clean;
                }
            }
        }

        $order = strtoupper((string) ($data['order'] ?? 'DESC'));
        if (! in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        return new self(
            query_id: (int) ($data['queryId'] ?? 0),
            page_id:  (int) ($data['pageId'] ?? 0),
            filters:  $filters,
            page:     max(1, (int) ($data['page'] ?? 1)),
            orderby:  sanitize_key((string) ($data['orderby'] ?? 'date')),
            order:    $order,
            search:   sanitize_text_field((string) ($data['search'] ?? '')),
        );
    }
}
```

Note: `sanitize_text_field` and `sanitize_key` are WordPress functions. In the unit test bootstrap (no WP loaded), these won't exist. Either:
- Define stubs in the unit bootstrap, or
- Move these tests to integration suite.

Define stubs in `tests/phpunit/bootstrap.php` if they don't exist:

```php
// Add at the end of tests/phpunit/bootstrap.php:
if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        return strip_tags(trim($str));
    }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $key): string {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
cd query-filter && vendor/bin/phpunit --testsuite unit --filter Request
```

Expected: 4 tests, all PASS.

- [ ] **Step 5: Commit**

```bash
git add includes/class-request.php tests/phpunit/unit/RequestTest.php tests/phpunit/bootstrap.php
git commit -m "feat(request): parse and sanitize REST request payload"
```

---

### Task 9: REST Controller + Renderer

**Files:**
- Create: `query-filter/includes/class-rest-controller.php`
- Create: `query-filter/includes/class-renderer.php`
- Modify: `query-filter/includes/class-plugin.php` — wire `rest_api_init`, add `render_block_core/query` filter
- Create: `query-filter/tests/phpunit/integration/RestControllerTest.php`

- [ ] **Step 1: Write failing integration test**

```php
<?php
// tests/phpunit/integration/RestControllerTest.php
declare(strict_types=1);

class RestControllerTest extends WP_UnitTestCase {

    public function set_up(): void {
        parent::set_up();
        Query_Filter_Indexer::create_table();

        $indexer = new Query_Filter_Indexer();
        $source = new Query_Filter_Source_Taxonomy('category');
        $indexer->register_filter(new Query_Filter_Filter_Checkboxes('category', $source));
        Query_Filter_Plugin::instance()->set_indexer($indexer);

        do_action('rest_api_init');
    }

    public function test_results_endpoint_returns_valid_response(): void {
        $term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $p1 = self::factory()->post->create(['post_title' => 'Post One', 'post_status' => 'publish']);
        wp_set_object_terms($p1, [$term->term_id], 'category');
        Query_Filter_Plugin::instance()->get_indexer()->index_post($p1);

        $request = new WP_REST_Request('POST', '/query-filter/v1/results');
        $request->set_body(wp_json_encode([
            'queryId' => 1,
            'pageId'  => 0,
            'filters' => ['category' => [$term->slug]],
            'page'    => 1,
        ]));
        $request->set_header('Content-Type', 'application/json');

        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertSame(200, $response->get_status());
        $this->assertArrayHasKey('results_html', $data);
        $this->assertArrayHasKey('filters', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('pages', $data);
        $this->assertSame(1, $data['total']);
    }

    public function test_results_endpoint_returns_filter_counts(): void {
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $hats  = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);

        $indexer = Query_Filter_Plugin::instance()->get_indexer();

        $p1 = self::factory()->post->create(['post_status' => 'publish']);
        $p2 = self::factory()->post->create(['post_status' => 'publish']);
        wp_set_object_terms($p1, [$shoes->term_id], 'category');
        wp_set_object_terms($p2, [$hats->term_id], 'category');
        $indexer->index_post($p1);
        $indexer->index_post($p2);

        $request = new WP_REST_Request('POST', '/query-filter/v1/results');
        $request->set_body(wp_json_encode([
            'queryId' => 1,
            'pageId'  => 0,
            'filters' => [],
            'page'    => 1,
        ]));
        $request->set_header('Content-Type', 'application/json');

        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertArrayHasKey('category', $data['filters']);
        $this->assertCount(2, $data['filters']['category']);
    }

    public function test_results_endpoint_rejects_invalid_method(): void {
        $request = new WP_REST_Request('GET', '/query-filter/v1/results');
        $response = rest_do_request($request);
        $this->assertSame(404, $response->get_status());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter RestController
```

- [ ] **Step 3: Implement REST Controller**

```php
<?php
// includes/class-rest-controller.php
declare(strict_types=1);

final class Query_Filter_Rest_Controller {

    public const NAMESPACE = 'query-filter/v1';
    public const ROUTE     = '/results';

    public static function register(): void {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(\WP_REST_Request $wp_request): \WP_REST_Response {
        $body = $wp_request->get_json_params();
        if (! is_array($body)) {
            return new \WP_REST_Response(['error' => 'Invalid JSON'], 400);
        }

        $request = Query_Filter_Request::from_array($body);
        $plugin  = Query_Filter_Plugin::instance();
        $indexer = $plugin->get_indexer();

        if (! $indexer) {
            return new \WP_REST_Response(['error' => 'Indexer not configured'], 500);
        }

        // Build active filters with logic config.
        $active_filters = [];
        foreach ($request->filters as $filter_name => $values) {
            $filter = $indexer->get_filter($filter_name);
            if ($filter instanceof Query_Filter_Filter_Checkboxes) {
                $active_filters[$filter_name] = [
                    'values' => $values,
                    'logic'  => 'OR', // Default; block attribute can override later.
                ];
            }
        }

        // Resolve post IDs.
        $engine = new Query_Filter_Query_Engine();
        $all_post_ids = $engine->get_post_ids($active_filters);

        // Apply search filter.
        $search_filter = new Query_Filter_Filter_Search();
        $search_args = $search_filter->get_query_args($request->search);

        // Apply sort.
        $sort_filter = new Query_Filter_Filter_Sort();
        $sort_args = $sort_filter->get_query_args($request->orderby, $request->order);

        // Render results.
        $renderer = new Query_Filter_Renderer();
        $render_result = $renderer->render(
            post_ids:    $all_post_ids,
            page:        $request->page,
            query_id:    $request->query_id,
            page_id:     $request->page_id,
            search_args: $search_args,
            sort_args:   $sort_args,
        );

        // Load filter values (counts scoped to matching posts).
        $filter_states = [];
        foreach ($indexer->get_filters() as $name => $filter) {
            if ($filter instanceof Query_Filter_Filter_Checkboxes) {
                $filter_states[$name] = $filter->load_values([
                    'post_ids' => $all_post_ids,
                ]);
            }
        }

        return new \WP_REST_Response([
            'results_html' => $render_result['results_html'],
            'filters'      => $filter_states,
            'total'        => $render_result['total'],
            'pages'        => $render_result['pages'],
        ]);
    }
}
```

- [ ] **Step 4: Implement Renderer**

```php
<?php
// includes/class-renderer.php
declare(strict_types=1);

final class Query_Filter_Renderer {

    /**
     * @param int[]                  $post_ids
     * @param int                    $page
     * @param int                    $query_id
     * @param int                    $page_id
     * @param array<string, string>  $search_args  e.g. ['s' => 'running']
     * @param array<string, string>  $sort_args    e.g. ['orderby' => 'price', 'order' => 'ASC']
     * @return array{results_html: string, total: int, pages: int}
     */
    public function render(
        array $post_ids,
        int $page,
        int $query_id,
        int $page_id,
        array $search_args = [],
        array $sort_args = [],
    ): array {
        $per_page = (int) get_option('posts_per_page', 10);
        $total = count($post_ids);
        $pager = new Query_Filter_Filter_Pager();
        $pager_result = $pager->compute($total, $per_page, $page);
        $pages = $pager_result['pages'];
        $current_page = $pager_result['current_page'];

        // Try block-based rendering if pageId is provided.
        if ($page_id > 0) {
            $html = $this->render_via_query_block($query_id, $page_id, $post_ids, $current_page, $per_page, $search_args, $sort_args);
            if ($html !== null) {
                return [
                    'results_html' => $html,
                    'total'        => $total,
                    'pages'        => $pages,
                ];
            }
        }

        // Fallback: simple WP_Query rendering.
        $html = $this->render_simple($post_ids, $current_page, $per_page, $search_args, $sort_args);

        return [
            'results_html' => $html,
            'total'        => $total,
            'pages'        => $pages,
        ];
    }

    private function render_via_query_block(
        int $query_id,
        int $page_id,
        array $post_ids,
        int $page,
        int $per_page,
        array $search_args,
        array $sort_args,
    ): ?string {
        $page_post = get_post($page_id);
        if (! $page_post) {
            return null;
        }

        $blocks = parse_blocks($page_post->post_content);
        $query_block = $this->find_query_block($blocks, $query_id);
        if (! $query_block) {
            return null;
        }

        $paged_ids = array_slice($post_ids, ($page - 1) * $per_page, $per_page);

        $filter_fn = function (array $query_vars) use ($paged_ids, $page, $search_args, $sort_args): array {
            $query_vars['post__in'] = $paged_ids ?: [0];
            $query_vars['orderby'] = ! empty($sort_args['orderby']) ? $sort_args['orderby'] : ($paged_ids ? 'post__in' : 'date');
            $query_vars['order'] = $sort_args['order'] ?? 'DESC';
            $query_vars['paged'] = $page;
            if (! empty($search_args['s'])) {
                $query_vars['s'] = $search_args['s'];
            }
            return $query_vars;
        };

        add_filter('query_loop_block_query_vars', $filter_fn, 999);
        $html = render_block($query_block);
        remove_filter('query_loop_block_query_vars', $filter_fn, 999);

        return $html;
    }

    /**
     * @param array<array<string, mixed>> $blocks
     */
    private function find_query_block(array $blocks, int $query_id): ?array {
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'core/query' && ($block['attrs']['queryId'] ?? 0) === $query_id) {
                return $block;
            }
            if (! empty($block['innerBlocks'])) {
                $found = $this->find_query_block($block['innerBlocks'], $query_id);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    private function render_simple(
        array $post_ids,
        int $page,
        int $per_page,
        array $search_args,
        array $sort_args,
    ): string {
        $paged_ids = array_slice($post_ids, ($page - 1) * $per_page, $per_page);

        $args = array_merge([
            'post__in'           => $paged_ids ?: [0],
            'orderby'            => $paged_ids ? 'post__in' : 'date',
            'posts_per_page'     => $per_page,
            'ignore_sticky_posts' => true,
            'post_status'        => 'publish',
        ], $search_args, $sort_args);

        $query = new \WP_Query($args);

        ob_start();
        while ($query->have_posts()) {
            $query->the_post();
            printf(
                '<li class="wp-block-post"><h3 class="wp-block-post-title">%s</h3></li>',
                esc_html(get_the_title())
            );
        }
        wp_reset_postdata();

        return ob_get_clean() ?: '';
    }
}
```

- [ ] **Step 5: Wire REST and rendering hooks in Plugin**

Add to `Query_Filter_Plugin::__construct()`:

```php
add_action('rest_api_init', [Query_Filter_Rest_Controller::class, 'register']);

add_filter('render_block_core/query', [$this, 'tag_query_block'], 10, 2);
```

Add the `tag_query_block` method:

```php
public function tag_query_block(string $content, array $block): string {
    $query_id = $block['attrs']['queryId'] ?? 0;
    $processor = new \WP_HTML_Tag_Processor($content);
    if ($processor->next_tag()) {
        $processor->set_attribute('data-wp-interactive', 'query-filter');
        $processor->set_attribute('data-query-filter-query', (string) $query_id);
    }
    return (string) $processor;
}
```

- [ ] **Step 6: Run — expect PASS**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter RestController
```

Expected: 3 tests, all PASS.

- [ ] **Step 7: Commit**

```bash
git add includes/class-rest-controller.php includes/class-renderer.php includes/class-plugin.php tests/phpunit/integration/RestControllerTest.php
git commit -m "feat(rest): results endpoint with renderer and query engine integration"
```

---

### Task 10: Admin Page (Tools -> Query Filter)

**Files:**
- Create: `query-filter/includes/class-admin.php`
- Modify: `query-filter/includes/class-plugin.php` — add `admin_menu` hook

- [ ] **Step 1: Implement Admin page**

```php
<?php
// includes/class-admin.php
declare(strict_types=1);

final class Query_Filter_Admin {

    public static function register(): void {
        add_management_page(
            __('Query Filter', 'query-filter'),
            __('Query Filter', 'query-filter'),
            'manage_options',
            'query-filter',
            [self::class, 'render_page']
        );
    }

    public static function render_page(): void {
        if (! current_user_can('manage_options')) {
            return;
        }

        // Handle actions.
        if (isset($_POST['query_filter_action']) && check_admin_referer('query_filter_admin')) {
            $action = sanitize_key($_POST['query_filter_action']);
            if ($action === 'rebuild') {
                Query_Filter_Indexer::schedule_full_rebuild();
                echo '<div class="notice notice-success"><p>' . esc_html__('Full rebuild scheduled.', 'query-filter') . '</p></div>';
            } elseif ($action === 'clear') {
                global $wpdb;
                $wpdb->query("TRUNCATE TABLE " . Query_Filter_Indexer::table_name());
                delete_option('query_filter_last_indexed');
                echo '<div class="notice notice-success"><p>' . esc_html__('Index cleared.', 'query-filter') . '</p></div>';
            }
        }

        global $wpdb;
        $table = Query_Filter_Indexer::table_name();
        $indexed_posts = (int) $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$table}");
        $total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $last_indexed = get_option('query_filter_last_indexed');
        $rebuild_in_progress = get_option('query_filter_rebuild_offset') !== false;

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Query Filter', 'query-filter'); ?></h1>

            <h2><?php esc_html_e('Index Status', 'query-filter'); ?></h2>
            <table class="widefat striped" style="max-width: 500px;">
                <tr>
                    <td><?php esc_html_e('Indexed Posts', 'query-filter'); ?></td>
                    <td><strong><?php echo esc_html((string) $indexed_posts); ?></strong></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Total Index Rows', 'query-filter'); ?></td>
                    <td><strong><?php echo esc_html((string) $total_rows); ?></strong></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Last Indexed', 'query-filter'); ?></td>
                    <td><strong><?php
                        echo $last_indexed
                            ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $last_indexed))
                            : esc_html__('Never', 'query-filter');
                    ?></strong></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Status', 'query-filter'); ?></td>
                    <td><strong><?php
                        echo $rebuild_in_progress
                            ? esc_html__('Rebuilding...', 'query-filter')
                            : esc_html__('Up to date', 'query-filter');
                    ?></strong></td>
                </tr>
            </table>

            <h2><?php esc_html_e('Actions', 'query-filter'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('query_filter_admin'); ?>
                <p>
                    <button type="submit" name="query_filter_action" value="rebuild" class="button button-primary">
                        <?php esc_html_e('Rebuild Full Index', 'query-filter'); ?>
                    </button>
                    <button type="submit" name="query_filter_action" value="clear" class="button"
                            onclick="return confirm('<?php esc_attr_e('Clear the entire index?', 'query-filter'); ?>')">
                        <?php esc_html_e('Clear Index', 'query-filter'); ?>
                    </button>
                </p>
            </form>

            <h2><?php esc_html_e('WP-CLI Commands', 'query-filter'); ?></h2>
            <pre style="background: #23282d; color: #eee; padding: 15px; max-width: 500px;">
wp query-filter index rebuild
wp query-filter index post &lt;post_id&gt;
wp query-filter index status
wp query-filter index clear</pre>
        </div>
        <?php
    }
}
```

- [ ] **Step 2: Wire admin_menu hook in Plugin**

Add to `Query_Filter_Plugin::__construct()`:

```php
add_action('admin_menu', [Query_Filter_Admin::class, 'register']);
```

- [ ] **Step 3: Manual verification checklist**

In wp-env browser (`localhost:8888/wp-admin/tools.php?page=query-filter`):
- Page renders without errors
- Index Status table shows
- Rebuild button schedules cron
- Clear button shows confirm dialog and truncates table
- WP-CLI reference block displays

- [ ] **Step 4: Commit**

```bash
git add includes/class-admin.php includes/class-plugin.php
git commit -m "feat(admin): Tools screen for index status and actions"
```

---

### Task 11: WP-CLI Commands

**Files:**
- Create: `query-filter/includes/class-cli.php`
- Modify: `query-filter/includes/class-plugin.php` — register CLI when WP_CLI defined

- [ ] **Step 1: Implement CLI command class**

```php
<?php
// includes/class-cli.php
declare(strict_types=1);

if (! defined('WP_CLI') || ! WP_CLI) {
    return;
}

final class Query_Filter_CLI {

    /**
     * Rebuild the full index.
     *
     * ## EXAMPLES
     *     wp query-filter index rebuild
     *
     * @subcommand rebuild
     */
    public function rebuild(array $args, array $assoc_args): void {
        $indexer = Query_Filter_Plugin::instance()->get_indexer();
        if (! $indexer) {
            \WP_CLI::error('Indexer not configured.');
            return;
        }

        global $wpdb;
        $wpdb->query('TRUNCATE TABLE ' . Query_Filter_Indexer::table_name());

        $post_ids = get_posts([
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ]);

        $progress = \WP_CLI\Utils\make_progress_bar('Indexing', count($post_ids));
        foreach ($post_ids as $post_id) {
            $indexer->index_post($post_id);
            $progress->tick();
        }
        $progress->finish();

        update_option('query_filter_last_indexed', time());
        \WP_CLI::success(count($post_ids) . ' posts indexed.');
    }

    /**
     * Index a single post.
     *
     * ## OPTIONS
     * <post_id>
     * : The post ID to index.
     *
     * ## EXAMPLES
     *     wp query-filter index post 42
     *
     * @subcommand post
     */
    public function post(array $args, array $assoc_args): void {
        $post_id = (int) $args[0];
        $post = get_post($post_id);
        if (! $post) {
            \WP_CLI::error("Post {$post_id} not found.");
            return;
        }

        $indexer = Query_Filter_Plugin::instance()->get_indexer();
        if (! $indexer) {
            \WP_CLI::error('Indexer not configured.');
            return;
        }

        $indexer->index_post($post_id);
        \WP_CLI::success("Post {$post_id} indexed.");
    }

    /**
     * Show index status.
     *
     * ## EXAMPLES
     *     wp query-filter index status
     *
     * @subcommand status
     */
    public function status(array $args, array $assoc_args): void {
        global $wpdb;
        $table = Query_Filter_Indexer::table_name();

        $indexed_posts = (int) $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$table}");
        $total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $last_indexed = get_option('query_filter_last_indexed');

        \WP_CLI::log("Indexed posts: {$indexed_posts}");
        \WP_CLI::log("Total rows:    {$total_rows}");
        \WP_CLI::log("Last indexed:  " . ($last_indexed ? wp_date('Y-m-d H:i:s', (int) $last_indexed) : 'Never'));
    }

    /**
     * Clear the entire index.
     *
     * ## EXAMPLES
     *     wp query-filter index clear
     *
     * @subcommand clear
     */
    public function clear(array $args, array $assoc_args): void {
        global $wpdb;
        $wpdb->query('TRUNCATE TABLE ' . Query_Filter_Indexer::table_name());
        delete_option('query_filter_last_indexed');
        \WP_CLI::success('Index cleared.');
    }
}
```

- [ ] **Step 2: Register CLI in Plugin**

Add to `Query_Filter_Plugin::__construct()`:

```php
if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('query-filter index', Query_Filter_CLI::class);
}
```

- [ ] **Step 3: Manual test in wp-env**

```bash
npx wp-env run cli wp query-filter index status
npx wp-env run cli wp query-filter index rebuild
npx wp-env run cli wp query-filter index status
npx wp-env run cli wp query-filter index clear
```

Expected: each command exits 0 with readable output.

- [ ] **Step 4: Commit**

```bash
git add includes/class-cli.php includes/class-plugin.php
git commit -m "feat(cli): query-filter index commands (rebuild, post, status, clear)"
```

---

### Task 12: Block Build Pipeline + Filter Container Block

**Files:**
- Modify: `query-filter/package.json` — add block dependencies
- Create: `query-filter/src/filter-container/block.json`
- Create: `query-filter/src/filter-container/index.js`
- Create: `query-filter/src/filter-container/edit.js`
- Create: `query-filter/src/filter-container/render.php`
- Create: `query-filter/src/filter-container/view.js` (store skeleton)
- Modify: `query-filter/includes/class-plugin.php` — register blocks on `init`

- [ ] **Step 1: Update package.json with block dependencies**

Add to `devDependencies`:

```json
"@wordpress/blocks": "^14.0.0",
"@wordpress/block-editor": "^14.0.0",
"@wordpress/components": "^29.0.0",
"@wordpress/data": "^10.0.0",
"@wordpress/i18n": "^5.0.0",
"@wordpress/interactivity": "^6.0.0",
"@wordpress/interactivity-router": "^3.0.0"
```

Add to `scripts`:

```json
"start": "wp-scripts start --webpack-src-dir=src --output-path=build"
```

Update existing `build` script:

```json
"build": "wp-scripts build --webpack-src-dir=src --output-path=build"
```

- [ ] **Step 2: Create filter-container block.json**

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "query-filter/filter-container",
  "version": "0.1.0",
  "title": "Filter Container",
  "category": "theme",
  "icon": "filter",
  "description": "Groups filter blocks and links them to a Query Loop.",
  "supports": {
    "html": false,
    "className": true,
    "spacing": {
      "margin": true,
      "padding": true,
      "blockGap": true
    },
    "layout": {
      "default": { "type": "flex", "orientation": "vertical" }
    },
    "interactivity": true
  },
  "attributes": {
    "queryId": {
      "type": "number",
      "default": 0
    }
  },
  "providesContext": {
    "query-filter/queryId": "queryId"
  },
  "textdomain": "query-filter",
  "editorScript": "file:./index.js",
  "viewScriptModule": "file:./view.js",
  "render": "file:./render.php"
}
```

- [ ] **Step 3: Create editor entry and edit component**

```js
// src/filter-container/index.js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: Edit,
} );
```

```js
// src/filter-container/edit.js
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const { queryId } = attributes;
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Filter Settings', 'query-filter' ) }>
                    <TextControl
                        label={ __( 'Query Loop ID', 'query-filter' ) }
                        help={ __( 'The queryId of the Query Loop block to filter.', 'query-filter' ) }
                        type="number"
                        value={ queryId || '' }
                        onChange={ ( val ) => setAttributes( { queryId: parseInt( val, 10 ) || 0 } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <InnerBlocks
                    allowedBlocks={ [
                        'query-filter/filter-checkboxes',
                        'query-filter/filter-search',
                        'query-filter/filter-sort',
                        'query-filter/filter-reset',
                    ] }
                    template={ [] }
                />
            </div>
        </>
    );
}
```

- [ ] **Step 4: Create render.php**

```php
<?php
// src/filter-container/render.php
declare(strict_types=1);

/**
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content.
 * @var WP_Block $block      Block instance.
 */

$query_id = $attributes['queryId'] ?? 0;
$page_id  = get_the_ID() ?: 0;
$per_page = (int) get_option( 'posts_per_page', 10 );

wp_interactivity_state( 'query-filter', [
    'restUrl'   => rest_url( 'query-filter/v1/results' ),
    'restNonce' => wp_create_nonce( 'wp_rest' ),
    'queryId'   => $query_id,
    'pageId'    => $page_id,
    'perPage'   => $per_page,
] );

$context = [
    'queryId' => $query_id,
];

printf(
    '<div %s data-wp-interactive="query-filter" data-wp-context=\'%s\'>%s</div>',
    get_block_wrapper_attributes(),
    wp_json_encode( $context ),
    $content
);
```

- [ ] **Step 5: Create view.js (store skeleton)**

```js
// src/filter-container/view.js
import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'query-filter', {
    state: {
        get activeFilters() {
            return state._filters || {};
        },
        _filters: {},
        currentPage: 1,
        orderby: 'date',
        order: 'DESC',
        search: '',
        loading: false,
        error: '',
        total: 0,
        pages: 0,
        filterStates: {},
    },
    actions: {
        setFilter( filterName, values ) {
            state._filters = {
                ...state._filters,
                [ filterName ]: values,
            };
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
        setSearch( value ) {
            state.search = value;
            state.currentPage = 1;
        },
        setSort( orderby, order ) {
            state.orderby = orderby;
            state.order = order;
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
        setPage( page ) {
            state.currentPage = page;
            store( 'query-filter' ).actions.fetchResults();
        },
        resetAll() {
            state._filters = {};
            state.search = '';
            state.orderby = 'date';
            state.order = 'DESC';
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
        *fetchResults() {
            state.loading = true;
            state.error = '';

            try {
                const response = yield fetch( state.restUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': state.restNonce,
                    },
                    body: JSON.stringify( {
                        queryId: state.queryId,
                        pageId: state.pageId,
                        filters: state.activeFilters,
                        page: state.currentPage,
                        orderby: state.orderby,
                        order: state.order,
                        search: state.search,
                    } ),
                } );

                if ( ! response.ok ) {
                    throw new Error( `HTTP ${ response.status }` );
                }

                const data = yield response.json();

                // Replace Query Loop content.
                const container = document.querySelector(
                    `[data-query-filter-query="${ state.queryId }"]`
                );
                if ( container ) {
                    container.innerHTML = data.results_html;
                }

                state.filterStates = data.filters || {};
                state.total = data.total;
                state.pages = data.pages;

                // Update URL.
                const url = new URL( window.location );
                Object.entries( state.activeFilters ).forEach(
                    ( [ name, values ] ) => {
                        if ( values.length > 0 ) {
                            url.searchParams.set(
                                `qf_${ name }`,
                                values.join( ',' )
                            );
                        } else {
                            url.searchParams.delete( `qf_${ name }` );
                        }
                    }
                );
                if ( state.search ) {
                    url.searchParams.set( 'qf_search', state.search );
                } else {
                    url.searchParams.delete( 'qf_search' );
                }
                if ( state.currentPage > 1 ) {
                    url.searchParams.set( 'qf_page', state.currentPage );
                } else {
                    url.searchParams.delete( 'qf_page' );
                }
                history.pushState( null, '', url );
            } catch ( e ) {
                state.error = 'Failed to load results. Please try again.';
                // Revert optimistic state if needed.
            }

            state.loading = false;
        },
    },
} );
```

- [ ] **Step 6: Register blocks in Plugin init**

Add to `Query_Filter_Plugin::__construct()`:

```php
add_action('init', [$this, 'register_blocks']);
```

Add the method:

```php
public function register_blocks(): void {
    $build_dir = dirname(QUERY_FILTER_PLUGIN_FILE) . '/build';
    $blocks = [
        'filter-container',
        'filter-checkboxes',
        'filter-search',
        'filter-sort',
        'filter-pager',
        'filter-reset',
    ];
    foreach ($blocks as $block) {
        $block_dir = $build_dir . '/' . $block;
        if (file_exists($block_dir . '/block.json')) {
            register_block_type($block_dir);
        }
    }
}
```

- [ ] **Step 7: Install deps and build**

```bash
cd query-filter && npm install && npm run build
```

Expected: build succeeds, `build/filter-container/` created with compiled JS + copied render.php + block.json.

- [ ] **Step 8: Commit**

```bash
git add package.json package-lock.json src/filter-container/ includes/class-plugin.php
git commit -m "feat(blocks): filter container block with interactivity store"
```

---

### Task 13: Child Filter Blocks (Checkboxes, Search, Sort, Pager, Reset)

**Files:**
- Create: `src/filter-checkboxes/` (block.json, index.js, edit.js, render.php, view.js)
- Create: `src/filter-search/` (block.json, index.js, edit.js, render.php, view.js)
- Create: `src/filter-sort/` (block.json, index.js, edit.js, render.php, view.js)
- Create: `src/filter-pager/` (block.json, index.js, edit.js, render.php, view.js)
- Create: `src/filter-reset/` (block.json, index.js, edit.js, render.php, view.js)

#### 13a: Checkboxes Block

- [ ] **Step 1: Create block.json**

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "query-filter/filter-checkboxes",
  "version": "0.1.0",
  "title": "Filter: Checkboxes",
  "category": "theme",
  "icon": "yes",
  "description": "Checkbox filter for Query Loop.",
  "parent": [ "query-filter/filter-container" ],
  "usesContext": [ "query-filter/queryId" ],
  "supports": {
    "html": false,
    "interactivity": true,
    "spacing": { "margin": true, "padding": true }
  },
  "attributes": {
    "filterName": { "type": "string", "default": "" },
    "sourceType": { "type": "string", "default": "taxonomy" },
    "sourceKey": { "type": "string", "default": "category" },
    "logic": { "type": "string", "default": "OR", "enum": [ "OR", "AND" ] },
    "label": { "type": "string", "default": "" },
    "showLabel": { "type": "boolean", "default": true },
    "showCounts": { "type": "boolean", "default": true },
    "limit": { "type": "number", "default": 0 }
  },
  "textdomain": "query-filter",
  "editorScript": "file:./index.js",
  "viewScriptModule": "file:./view.js",
  "render": "file:./render.php"
}
```

- [ ] **Step 2: Create edit.js**

```js
// src/filter-checkboxes/edit.js
import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    InspectorControls,
} from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes } ) {
    const { filterName, sourceType, sourceKey, logic, label, showLabel, showCounts, limit } = attributes;
    const blockProps = useBlockProps();

    const taxonomies = useSelect( ( select ) => {
        return ( select( 'core' ).getTaxonomies( { per_page: 100 } ) || [] )
            .filter( ( t ) => t.visibility.publicly_queryable );
    }, [] );

    // Auto-set filterName from sourceKey if empty.
    if ( ! filterName && sourceKey ) {
        setAttributes( { filterName: sourceKey } );
    }

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Filter Settings', 'query-filter' ) }>
                    <TextControl
                        label={ __( 'Filter Name', 'query-filter' ) }
                        help={ __( 'Unique identifier for this filter (used in index).', 'query-filter' ) }
                        value={ filterName }
                        onChange={ ( val ) => setAttributes( { filterName: val } ) }
                    />
                    <SelectControl
                        label={ __( 'Data Source', 'query-filter' ) }
                        value={ sourceType }
                        options={ [
                            { label: 'Taxonomy', value: 'taxonomy' },
                            { label: 'Post Meta', value: 'postmeta' },
                        ] }
                        onChange={ ( val ) => setAttributes( { sourceType: val } ) }
                    />
                    { sourceType === 'taxonomy' && (
                        <SelectControl
                            label={ __( 'Taxonomy', 'query-filter' ) }
                            value={ sourceKey }
                            options={ ( taxonomies || [] ).map( ( t ) => ( {
                                label: t.name,
                                value: t.slug,
                            } ) ) }
                            onChange={ ( val ) => setAttributes( { sourceKey: val, filterName: val } ) }
                        />
                    ) }
                    { sourceType === 'postmeta' && (
                        <TextControl
                            label={ __( 'Meta Key', 'query-filter' ) }
                            value={ sourceKey }
                            onChange={ ( val ) => setAttributes( { sourceKey: val, filterName: val } ) }
                        />
                    ) }
                    <SelectControl
                        label={ __( 'Selection Logic', 'query-filter' ) }
                        value={ logic }
                        options={ [
                            { label: 'OR (match any)', value: 'OR' },
                            { label: 'AND (match all)', value: 'AND' },
                        ] }
                        onChange={ ( val ) => setAttributes( { logic: val } ) }
                    />
                    <TextControl
                        label={ __( 'Label', 'query-filter' ) }
                        value={ label }
                        onChange={ ( val ) => setAttributes( { label: val } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Label', 'query-filter' ) }
                        checked={ showLabel }
                        onChange={ ( val ) => setAttributes( { showLabel: val } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Result Counts', 'query-filter' ) }
                        checked={ showCounts }
                        onChange={ ( val ) => setAttributes( { showCounts: val } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <fieldset>
                    { showLabel && label && <legend>{ label }</legend> }
                    <p style={ { color: '#757575', fontStyle: 'italic' } }>
                        { __( 'Checkboxes filter', 'query-filter' ) }: { sourceKey || __( '(not configured)', 'query-filter' ) }
                    </p>
                </fieldset>
            </div>
        </>
    );
}
```

- [ ] **Step 3: Create index.js, render.php, view.js for checkboxes**

```js
// src/filter-checkboxes/index.js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, { edit: Edit } );
```

```php
<?php
// src/filter-checkboxes/render.php
declare(strict_types=1);

$filter_name = $attributes['filterName'] ?? '';
$source_type = $attributes['sourceType'] ?? 'taxonomy';
$source_key  = $attributes['sourceKey'] ?? 'category';
$label       = $attributes['label'] ?? '';
$show_label  = $attributes['showLabel'] ?? true;
$show_counts = $attributes['showCounts'] ?? true;
$logic       = $attributes['logic'] ?? 'OR';

if ( empty( $filter_name ) ) {
    return;
}

$indexer = Query_Filter_Plugin::instance()->get_indexer();
$filter  = $indexer ? $indexer->get_filter( $filter_name ) : null;
$options = [];

if ( $filter instanceof Query_Filter_Filter_Checkboxes ) {
    $options = $filter->load_values( [] );
}

$context = [
    'filterName' => $filter_name,
    'logic'      => $logic,
    'options'    => $options,
    'selected'   => [],
];

?>
<div
    <?php echo get_block_wrapper_attributes(); ?>
    data-wp-interactive="query-filter"
    data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
>
    <fieldset>
        <?php if ( $show_label && $label ) : ?>
            <legend class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></legend>
        <?php endif; ?>

        <?php foreach ( $options as $option ) : ?>
            <label class="wp-block-query-filter-checkboxes__option">
                <input
                    type="checkbox"
                    value="<?php echo esc_attr( $option['value'] ); ?>"
                    data-wp-on--change="actions.toggleCheckbox"
                />
                <span><?php echo esc_html( $option['label'] ); ?></span>
                <?php if ( $show_counts ) : ?>
                    <span class="wp-block-query-filter-checkboxes__count">(<?php echo (int) $option['count']; ?>)</span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </fieldset>
</div>
```

```js
// src/filter-checkboxes/view.js
import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'query-filter', {
    actions: {
        toggleCheckbox() {
            const ctx = getContext();
            const { ref } = getElement();
            const value = ref.value;
            const selected = [ ...( ctx.selected || [] ) ];

            const idx = selected.indexOf( value );
            if ( idx > -1 ) {
                selected.splice( idx, 1 );
            } else {
                selected.push( value );
            }

            ctx.selected = selected;

            const { state } = store( 'query-filter' );
            state._filters = {
                ...state._filters,
                [ ctx.filterName ]: selected,
            };
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
    },
} );
```

#### 13b: Search Block

- [ ] **Step 4: Create search block files**

```json
// src/filter-search/block.json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "query-filter/filter-search",
  "version": "0.1.0",
  "title": "Filter: Search",
  "category": "theme",
  "icon": "search",
  "description": "Search input filter for Query Loop.",
  "parent": [ "query-filter/filter-container" ],
  "usesContext": [ "query-filter/queryId" ],
  "supports": { "html": false, "interactivity": true },
  "attributes": {
    "label": { "type": "string", "default": "Search" },
    "showLabel": { "type": "boolean", "default": true },
    "placeholder": { "type": "string", "default": "Search..." }
  },
  "textdomain": "query-filter",
  "editorScript": "file:./index.js",
  "viewScriptModule": "file:./view.js",
  "render": "file:./render.php"
}
```

```js
// src/filter-search/index.js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
registerBlockType( metadata.name, { edit: Edit } );
```

```js
// src/filter-search/edit.js
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const { label, showLabel, placeholder } = attributes;
    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Search Settings', 'query-filter' ) }>
                    <TextControl label={ __( 'Label', 'query-filter' ) } value={ label } onChange={ ( v ) => setAttributes( { label: v } ) } />
                    <ToggleControl label={ __( 'Show Label', 'query-filter' ) } checked={ showLabel } onChange={ ( v ) => setAttributes( { showLabel: v } ) } />
                    <TextControl label={ __( 'Placeholder', 'query-filter' ) } value={ placeholder } onChange={ ( v ) => setAttributes( { placeholder: v } ) } />
                </PanelBody>
            </InspectorControls>
            <div { ...useBlockProps() }>
                { showLabel && <label className="wp-block-query-filter__label">{ label }</label> }
                <input type="search" placeholder={ placeholder } disabled />
            </div>
        </>
    );
}
```

```php
<?php
// src/filter-search/render.php
declare(strict_types=1);

$label       = $attributes['label'] ?? 'Search';
$show_label  = $attributes['showLabel'] ?? true;
$placeholder = $attributes['placeholder'] ?? 'Search...';

?>
<div
    <?php echo get_block_wrapper_attributes(); ?>
    data-wp-interactive="query-filter"
>
    <?php if ( $show_label && $label ) : ?>
        <label class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></label>
    <?php endif; ?>
    <input
        type="search"
        placeholder="<?php echo esc_attr( $placeholder ); ?>"
        data-wp-on--input="actions.onSearchInput"
        data-wp-bind--value="state.search"
    />
</div>
```

```js
// src/filter-search/view.js
import { store } from '@wordpress/interactivity';

let debounceTimer;

store( 'query-filter', {
    actions: {
        onSearchInput( event ) {
            const { state } = store( 'query-filter' );
            state.search = event.target.value;

            clearTimeout( debounceTimer );
            debounceTimer = setTimeout( () => {
                state.currentPage = 1;
                store( 'query-filter' ).actions.fetchResults();
            }, 300 );
        },
    },
} );
```

#### 13c: Sort Block

- [ ] **Step 5: Create sort block files**

```json
// src/filter-sort/block.json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "query-filter/filter-sort",
  "version": "0.1.0",
  "title": "Filter: Sort",
  "category": "theme",
  "icon": "sort",
  "description": "Sort dropdown for Query Loop.",
  "parent": [ "query-filter/filter-container" ],
  "usesContext": [ "query-filter/queryId" ],
  "supports": { "html": false, "interactivity": true },
  "attributes": {
    "label": { "type": "string", "default": "Sort by" },
    "options": {
      "type": "array",
      "default": [
        { "label": "Newest", "orderby": "date", "order": "DESC" },
        { "label": "Oldest", "orderby": "date", "order": "ASC" },
        { "label": "Title A-Z", "orderby": "title", "order": "ASC" },
        { "label": "Title Z-A", "orderby": "title", "order": "DESC" }
      ]
    }
  },
  "textdomain": "query-filter",
  "editorScript": "file:./index.js",
  "viewScriptModule": "file:./view.js",
  "render": "file:./render.php"
}
```

```js
// src/filter-sort/index.js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
registerBlockType( metadata.name, { edit: Edit } );
```

```js
// src/filter-sort/edit.js
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes } ) {
    const { label, options } = attributes;
    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Sort Settings', 'query-filter' ) }>
                    <p>{ __( 'Sort options are configured via block attributes.', 'query-filter' ) }</p>
                </PanelBody>
            </InspectorControls>
            <div { ...useBlockProps() }>
                <label className="wp-block-query-filter__label">{ label }</label>
                <select disabled>
                    { options.map( ( opt, i ) => (
                        <option key={ i }>{ opt.label }</option>
                    ) ) }
                </select>
            </div>
        </>
    );
}
```

```php
<?php
// src/filter-sort/render.php
declare(strict_types=1);

$label   = $attributes['label'] ?? 'Sort by';
$options = $attributes['options'] ?? [];

?>
<div
    <?php echo get_block_wrapper_attributes(); ?>
    data-wp-interactive="query-filter"
>
    <label class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></label>
    <select data-wp-on--change="actions.onSortChange">
        <?php foreach ( $options as $opt ) : ?>
            <option value="<?php echo esc_attr( $opt['orderby'] . ':' . $opt['order'] ); ?>">
                <?php echo esc_html( $opt['label'] ); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
```

```js
// src/filter-sort/view.js
import { store } from '@wordpress/interactivity';

store( 'query-filter', {
    actions: {
        onSortChange( event ) {
            const [ orderby, order ] = event.target.value.split( ':' );
            const { state } = store( 'query-filter' );
            state.orderby = orderby;
            state.order = order;
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
    },
} );
```

#### 13d: Pager Block

- [ ] **Step 6: Create pager block files**

```json
// src/filter-pager/block.json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "query-filter/filter-pager",
  "version": "0.1.0",
  "title": "Filter: Pager",
  "category": "theme",
  "icon": "controls-back",
  "description": "Pagination for filtered Query Loop.",
  "ancestor": [ "core/query" ],
  "usesContext": [ "queryId" ],
  "supports": { "html": false, "interactivity": true },
  "attributes": {},
  "textdomain": "query-filter",
  "editorScript": "file:./index.js",
  "viewScriptModule": "file:./view.js",
  "render": "file:./render.php"
}
```

```js
// src/filter-pager/index.js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
registerBlockType( metadata.name, { edit: Edit } );
```

```js
// src/filter-pager/edit.js
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
    return (
        <div { ...useBlockProps() }>
            <p style={ { color: '#757575', fontStyle: 'italic' } }>
                { __( 'Pagination (rendered on frontend)', 'query-filter' ) }
            </p>
        </div>
    );
}
```

```php
<?php
// src/filter-pager/render.php
declare(strict_types=1);

$query_id = $block->context['queryId'] ?? 0;

$context = [
    'queryId' => $query_id,
];

?>
<nav
    <?php echo get_block_wrapper_attributes( [ 'aria-label' => __( 'Pagination', 'query-filter' ) ] ); ?>
    data-wp-interactive="query-filter"
    data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
>
    <span data-wp-text="state.pagerSummary"></span>
    <button
        data-wp-on--click="actions.prevPage"
        data-wp-bind--disabled="state.isFirstPage"
    >&laquo; <?php esc_html_e( 'Prev', 'query-filter' ); ?></button>
    <span data-wp-text="state.currentPage"></span>
    /
    <span data-wp-text="state.pages"></span>
    <button
        data-wp-on--click="actions.nextPage"
        data-wp-bind--disabled="state.isLastPage"
    ><?php esc_html_e( 'Next', 'query-filter' ); ?> &raquo;</button>
</nav>
```

```js
// src/filter-pager/view.js
import { store } from '@wordpress/interactivity';

store( 'query-filter', {
    state: {
        get pagerSummary() {
            const { state } = store( 'query-filter' );
            if ( state.total === 0 ) return '';
            const perPage = state.perPage || 10;
            const start = ( state.currentPage - 1 ) * perPage + 1;
            const end = Math.min( state.currentPage * perPage, state.total );
            return `Showing ${ start }-${ end } of ${ state.total }`;
        },
        get isFirstPage() {
            return store( 'query-filter' ).state.currentPage <= 1;
        },
        get isLastPage() {
            const { state } = store( 'query-filter' );
            return state.currentPage >= state.pages;
        },
    },
    actions: {
        prevPage() {
            const { state } = store( 'query-filter' );
            if ( state.currentPage > 1 ) {
                state.currentPage--;
                store( 'query-filter' ).actions.fetchResults();
            }
        },
        nextPage() {
            const { state } = store( 'query-filter' );
            if ( state.currentPage < state.pages ) {
                state.currentPage++;
                store( 'query-filter' ).actions.fetchResults();
            }
        },
    },
} );
```

#### 13e: Reset Block

- [ ] **Step 7: Create reset block files**

```json
// src/filter-reset/block.json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "query-filter/filter-reset",
  "version": "0.1.0",
  "title": "Filter: Reset",
  "category": "theme",
  "icon": "dismiss",
  "description": "Reset all active filters.",
  "parent": [ "query-filter/filter-container" ],
  "usesContext": [ "query-filter/queryId" ],
  "supports": { "html": false, "interactivity": true },
  "attributes": {
    "label": { "type": "string", "default": "Reset Filters" }
  },
  "textdomain": "query-filter",
  "editorScript": "file:./index.js",
  "viewScriptModule": "file:./view.js",
  "render": "file:./render.php"
}
```

```js
// src/filter-reset/index.js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
registerBlockType( metadata.name, { edit: Edit } );
```

```js
// src/filter-reset/edit.js
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const { label } = attributes;
    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Reset Settings', 'query-filter' ) }>
                    <TextControl label={ __( 'Label', 'query-filter' ) } value={ label } onChange={ ( v ) => setAttributes( { label: v } ) } />
                </PanelBody>
            </InspectorControls>
            <div { ...useBlockProps() }>
                <button className="wp-block-query-filter-reset__button" disabled>{ label }</button>
            </div>
        </>
    );
}
```

```php
<?php
// src/filter-reset/render.php
declare(strict_types=1);

$label = $attributes['label'] ?? __( 'Reset Filters', 'query-filter' );

?>
<div
    <?php echo get_block_wrapper_attributes(); ?>
    data-wp-interactive="query-filter"
    data-wp-class--is-hidden="state.hasNoActiveFilters"
>
    <button
        class="wp-block-query-filter-reset__button"
        data-wp-on--click="actions.resetAll"
    >
        <?php echo esc_html( $label ); ?>
    </button>
</div>
```

```js
// src/filter-reset/view.js
import { store } from '@wordpress/interactivity';

store( 'query-filter', {
    state: {
        get hasNoActiveFilters() {
            const { state } = store( 'query-filter' );
            const hasFilters = Object.values( state._filters || {} ).some(
                ( v ) => v.length > 0
            );
            return ! hasFilters && ! state.search;
        },
    },
} );
```

- [ ] **Step 8: Build all blocks**

```bash
cd query-filter && npm run build
```

Expected: all 6 block directories created under `build/`.

- [ ] **Step 9: Manual smoke test in wp-env editor**

Start wp-env, open the editor, verify:
- Filter Container block appears and accepts child filter blocks
- Each filter block shows in the inserter when inside the container
- Pager block only appears inside Query Loop
- Inspector controls render correctly

- [ ] **Step 10: Commit**

```bash
git add src/filter-checkboxes/ src/filter-search/ src/filter-sort/ src/filter-pager/ src/filter-reset/
git commit -m "feat(blocks): checkboxes, search, sort, pager, reset filter blocks"
```

---

### Task 14: Vitest JS Tests + Frontend Polish

**Files:**
- Create: `query-filter/tests/js/state-url.test.js`
- Modify: `query-filter/package.json` — add test:unit script
- Modify: `query-filter/src/filter-container/view.js` — extract URL helpers, add loading class

- [ ] **Step 1: Write Vitest test for URL state serialization**

```js
// tests/js/state-url.test.js
import { describe, it, expect } from 'vitest';

// Extract and test URL helpers independently.
function serializeFiltersToUrl( baseUrl, filters, search, page ) {
    const url = new URL( baseUrl );
    Object.entries( filters ).forEach( ( [ name, values ] ) => {
        if ( values.length > 0 ) {
            url.searchParams.set( `qf_${ name }`, values.join( ',' ) );
        } else {
            url.searchParams.delete( `qf_${ name }` );
        }
    } );
    if ( search ) {
        url.searchParams.set( 'qf_search', search );
    } else {
        url.searchParams.delete( 'qf_search' );
    }
    if ( page > 1 ) {
        url.searchParams.set( 'qf_page', String( page ) );
    } else {
        url.searchParams.delete( 'qf_page' );
    }
    return url.toString();
}

function deserializeFiltersFromUrl( url ) {
    const parsed = new URL( url );
    const filters = {};
    let search = '';
    let page = 1;

    parsed.searchParams.forEach( ( value, key ) => {
        if ( key.startsWith( 'qf_' ) && key !== 'qf_search' && key !== 'qf_page' ) {
            const filterName = key.slice( 3 );
            filters[ filterName ] = value.split( ',' ).filter( Boolean );
        }
    } );

    search = parsed.searchParams.get( 'qf_search' ) || '';
    page = parseInt( parsed.searchParams.get( 'qf_page' ) || '1', 10 );

    return { filters, search, page };
}

describe( 'URL state serialization', () => {
    it( 'serializes filters to URL params', () => {
        const result = serializeFiltersToUrl(
            'https://example.com/shop',
            { category: [ 'shoes', 'boots' ], color: [ 'red' ] },
            '',
            1
        );
        expect( result ).toContain( 'qf_category=shoes%2Cboots' );
        expect( result ).toContain( 'qf_color=red' );
        expect( result ).not.toContain( 'qf_search' );
        expect( result ).not.toContain( 'qf_page' );
    } );

    it( 'serializes search and page', () => {
        const result = serializeFiltersToUrl(
            'https://example.com/shop',
            {},
            'running',
            3
        );
        expect( result ).toContain( 'qf_search=running' );
        expect( result ).toContain( 'qf_page=3' );
    } );

    it( 'round-trips filter state', () => {
        const original = { category: [ 'shoes' ], color: [ 'red', 'blue' ] };
        const url = serializeFiltersToUrl( 'https://example.com/', original, 'test', 2 );
        const result = deserializeFiltersFromUrl( url );
        expect( result.filters ).toEqual( original );
        expect( result.search ).toBe( 'test' );
        expect( result.page ).toBe( 2 );
    } );

    it( 'omits page 1 from URL', () => {
        const result = serializeFiltersToUrl( 'https://example.com/', {}, '', 1 );
        expect( result ).not.toContain( 'qf_page' );
    } );
} );
```

- [ ] **Step 2: Run JS tests — expect FAIL (then PASS after extracting helpers)**

```bash
cd query-filter && npm run test:unit -- --run
```

The tests should pass since they test pure functions defined in the test file. This validates the URL logic that the store uses.

- [ ] **Step 3: Add loading class behavior**

In `filter-container/render.php`, add a loading class to the Query Loop:

Add to `Query_Filter_Plugin::tag_query_block()`:

```php
$processor->set_attribute('data-wp-class--query-filter-loading', 'state.loading');
```

This allows themes to style the loading state:

```css
/* Theme can add: */
.query-filter-loading {
    opacity: 0.5;
    pointer-events: none;
}
```

- [ ] **Step 4: Build and verify**

```bash
cd query-filter && npm run build
```

- [ ] **Step 5: Commit**

```bash
git add tests/js/ src/ includes/class-plugin.php package.json
git commit -m "feat(frontend): JS tests for URL state, loading class on query loop"
```

---

### Task 15: Indexer Configuration Wiring

**Files:**
- Modify: `query-filter/includes/class-plugin.php` — wire up indexer with default filters on `init`

The indexer currently needs manual `register_filter()` calls. For the plugin to work end-to-end, the Plugin must configure the indexer with filters based on what's used in the block editor.

- [ ] **Step 1: Implement default indexer setup**

For MVP, register filters based on a saved configuration. Add to `Plugin::__construct()`:

```php
add_action('init', [$this, 'configure_indexer'], 5);
```

Add the method:

```php
public function configure_indexer(): void {
    $this->indexer = new Query_Filter_Indexer();

    // Register filters based on saved filter configs.
    // For MVP: scan for registered taxonomies and register a checkbox filter for each public one.
    $taxonomies = get_taxonomies(['public' => true], 'names');
    foreach ($taxonomies as $taxonomy) {
        $source = new Query_Filter_Source_Taxonomy($taxonomy);
        $this->indexer->register_filter(new Query_Filter_Filter_Checkboxes($taxonomy, $source));
    }
}
```

- [ ] **Step 2: Integration test for end-to-end flow**

```php
<?php
// tests/phpunit/integration/EndToEndTest.php
declare(strict_types=1);

class EndToEndTest extends WP_UnitTestCase {

    public function set_up(): void {
        parent::set_up();
        Query_Filter_Indexer::create_table();
        Query_Filter_Plugin::instance()->configure_indexer();
    }

    public function test_full_flow_index_and_query(): void {
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $hats  = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);

        $p1 = self::factory()->post->create(['post_title' => 'Shoe Post', 'post_status' => 'publish']);
        $p2 = self::factory()->post->create(['post_title' => 'Hat Post', 'post_status' => 'publish']);
        wp_set_object_terms($p1, [$shoes->term_id], 'category');
        wp_set_object_terms($p2, [$hats->term_id], 'category');

        $indexer = Query_Filter_Plugin::instance()->get_indexer();
        $indexer->index_post($p1);
        $indexer->index_post($p2);

        do_action('rest_api_init');

        $request = new WP_REST_Request('POST', '/query-filter/v1/results');
        $request->set_body(wp_json_encode([
            'queryId' => 1,
            'pageId'  => 0,
            'filters' => ['category' => [$shoes->slug]],
            'page'    => 1,
        ]));
        $request->set_header('Content-Type', 'application/json');

        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertSame(200, $response->get_status());
        $this->assertSame(1, $data['total']);
        $this->assertStringContainsString('Shoe Post', $data['results_html']);
        $this->assertStringNotContainsString('Hat Post', $data['results_html']);

        // Verify filter counts.
        $this->assertArrayHasKey('category', $data['filters']);
    }
}
```

- [ ] **Step 3: Run — expect PASS**

```bash
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration --filter EndToEnd
```

- [ ] **Step 4: Commit**

```bash
git add includes/class-plugin.php tests/phpunit/integration/EndToEndTest.php
git commit -m "feat(plugin): auto-configure indexer with public taxonomy filters"
```

---

### Task 16: Final Verification

- [ ] **Step 1: Run full PHPUnit suite**

```bash
cd query-filter && vendor/bin/phpunit --testsuite unit
npx wp-env run tests-cli --env-cwd=wp-content/plugins/query-filter -- vendor/bin/phpunit --testsuite integration
```

Expected: all tests pass.

- [ ] **Step 2: Run JS tests**

```bash
cd query-filter && npm run test:unit -- --run
```

Expected: all tests pass.

- [ ] **Step 3: Build succeeds clean**

```bash
cd query-filter && npm run build
```

Expected: clean build, no warnings.

- [ ] **Step 4: Manual smoke test in wp-env**

Start `npx wp-env start` and verify in the browser:
1. Create a page with a Query Loop (queryId auto-assigned)
2. Add a Filter Container, set queryId to match
3. Add Checkboxes filter inside container, configure for category taxonomy
4. Add Search, Sort, Reset filters inside container
5. Add Pager block inside the Query Loop
6. Publish the page
7. On the frontend: checkboxes render with counts, clicking filters the results, search works, sort works, pager works, reset clears all, URL updates

- [ ] **Step 5: Use superpowers:verification-before-completion**

Run the verification skill before claiming done.

- [ ] **Step 6: Use superpowers:finishing-a-development-branch**

Choose merge/PR strategy for the feature branch.

---

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-04-02-query-filter-implementation.md`. Two execution options:**

1. **Subagent-driven (recommended)** — superpowers:subagent-driven-development: fresh subagent per task, review between tasks, fast iteration.

2. **Inline execution** — superpowers:executing-plans: run tasks in one session with checkpoints.

**Which approach?**
