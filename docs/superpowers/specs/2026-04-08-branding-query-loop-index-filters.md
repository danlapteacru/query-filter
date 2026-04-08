# Branding — Query Loop Index Filters

**Status:** Adopted (8 April 2026)

## Winner

**Display name:** Query Loop Index Filters

**Canonical WordPress.org slug:** `query-loop-index-filters`

**Tagline (≤10 words):** Index-backed filters for the core Query Loop.

## Slug verification note

Re-check `https://wordpress.org/plugins/query-loop-index-filters/` immediately before submission. As of 8 April 2026 the URL did not resolve to an occupied plugin page (directory search fallback). WordPress.org slugs are effectively **permanent** after approval.

## Rationale

“Query Loop Index Filters” aligns the name with how WordPress users already conceptualise the surface area: core documentation presents the Query Loop as an advanced block, and “Query Loop” is the label people search for when building block-theme archives and listings.

Adding **Index** signals the main differentiator—fast post-ID resolution via a **precomputed table**—without forcing jargon like “faceted search” into the display name. In the WordPress market, “facets” often implies a wider set of facet types (date ranges, sliders, maps). The free plugin’s scope is **discrete** facets only; that naming choice reduces expectation drift.

The name mitigates two concrete risks:

1. It does not collide with the legacy WordPress.org plugin identity that uses the slug **`query-filter`**.
2. It reduces confusion with Human Made’s publicly described **“Query Loop Filter”** by inserting an unmistakable differentiator (**Index**) into the name.

The slug bakes in `query-loop`, which matches long-term intent even if branding or scope evolve slightly.

**Trademark:** Independent legal checks for the chosen name are out of scope for this doc and should be done separately if needed.

## Launch recommendations

1. **Slug check:** Re-verify `query-loop-index-filters` right before .org submission.
2. **Readme scope line (top):** State discrete-only facets explicitly so users do not expect range/date/map in the free package.
3. **Onboarding:** First-run flow: choose a Query Loop → add container → add one taxonomy facet (Query Loop is “advanced” and easy to misconfigure without guardrails).

## Technical identifiers (unchanged)

To avoid breaking existing content and clients, these stay as **`query-filter`** until a deliberate migration:

- REST namespace: `query-filter/v1`
- Block names: `query-filter/filter-*`
- Interactivity store: `query-filter`
- WP-CLI: `wp query-filter index …`
- HTML hooks: `data-query-filter-query`, `data-wp-interactive="query-filter"`
- Block CSS classes: `wp-block-query-filter-*`

**Text domain** for translations: `query-loop-index-filters` (matches the intended directory slug per WordPress guidelines).

## Plugin bootstrap file

The main PHP file is **`query-loop-index-filters.php`** (matches the intended WordPress.org slug). PHP classes live under **`includes/`** with the **`QLIF_*`** prefix and **`qlif-*.php`** filenames. REST routes, block names, the Interactivity store id, and WP-CLI commands remain **`query-filter`-prefixed** as listed above.
